<?php

declare(strict_types=1);

namespace Tests\Feature\Public;

use App\Enums\CompanyPackageSku;
use App\Models\CommercialSignupIntent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PublicSignupFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_welcome_shows_commercial_card(): void
    {
        $this->seed();

        $response = $this->get('/');
        $response->assertOk();
        $response->assertSee('Ver planes y contratar');
        $response->assertSee('Licencia SaaS');
    }

    public function test_public_signup_creates_user_only_on_approved_payment(): void
    {
        $this->seed();

        $create = $this->get(route('signup.create', [
            'sku' => CompanyPackageSku::Pack1Manual->value,
            'cycle' => 'monthly',
        ]));
        $create->assertRedirect();

        $intent = CommercialSignupIntent::query()->firstOrFail();

        $this->post(route('signup.data.store', $intent), [
            'party_type' => 'legal_entity',
            'legal_name' => 'Vigilancia Andina S.A.S.',
            'trade_name' => 'Vigilancia Andina',
            'tax_id' => '901999888-1',
            'admin_name' => 'Admin Andina',
            'email' => 'admin@vigilancia-andina.test',
            'phone' => '+57 300 111 0000',
            'password' => 'Empresa123!',
            'password_confirmation' => 'Empresa123!',
        ])->assertRedirect(route('signup.legal', $intent));

        $this->post(route('signup.legal.store', $intent), [
            'representative_name' => 'María López',
            'representative_role' => 'Gerente',
            'representative_document_type' => 'CC',
            'representative_document_number' => '52123456',
            ...$this->acceptAllCorpusDocs(CompanyPackageSku::Pack1Manual),
        ])->assertRedirect(route('signup.summary', $intent));

        $this->post(route('signup.pay', $intent))
            ->assertRedirect(route('signup.checkout.show', $intent));

        $this->assertDatabaseMissing('users', ['email' => 'admin@vigilancia-andina.test']);

        $reject = $this->post(route('signup.checkout.reject', $intent));
        $reject->assertRedirect(route('planes.index'));
        $this->assertDatabaseMissing('users', ['email' => 'admin@vigilancia-andina.test']);
    }

    public function test_approved_public_signup_activates_account(): void
    {
        $this->seed();

        $this->get(route('signup.create', [
            'sku' => CompanyPackageSku::Pack1Manual->value,
            'cycle' => 'monthly',
        ]));

        $intent = CommercialSignupIntent::query()->firstOrFail();

        $this->post(route('signup.data.store', $intent), [
            'party_type' => 'legal_entity',
            'legal_name' => 'Norte Vigilancia S.A.S.',
            'trade_name' => 'Norte Vigilancia',
            'tax_id' => '901888777-2',
            'admin_name' => 'Admin Norte',
            'email' => 'admin@norte-vigilancia.test',
            'password' => 'Empresa123!',
            'password_confirmation' => 'Empresa123!',
        ]);

        $this->post(route('signup.legal.store', $intent), [
            'representative_name' => 'Carlos Pérez',
            'representative_role' => 'Rep. Legal',
            'representative_document_type' => 'CC',
            'representative_document_number' => '80123456',
            ...$this->acceptAllCorpusDocs(CompanyPackageSku::Pack1Manual),
        ]);

        $this->post(route('signup.pay', $intent));

        $approve = $this->post(route('signup.checkout.approve', $intent));
        $approve->assertRedirect(route('company.dashboard'));

        $this->assertDatabaseHas('users', ['email' => 'admin@norte-vigilancia.test']);
        $this->assertDatabaseHas('security_companies', ['tax_id' => '901888777-2']);
        $this->assertDatabaseHas('subscription_acceptances', ['representative_name' => 'Carlos Pérez']);
        $this->assertDatabaseHas('platform_documents', ['type' => 'invoice', 'is_demo' => true]);

        $user = User::query()->where('email', 'admin@norte-vigilancia.test')->first();
        $this->assertAuthenticatedAs($user);
    }
}
