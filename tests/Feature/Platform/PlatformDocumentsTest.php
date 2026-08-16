<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\SecurityCompany;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class PlatformDocumentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_access_documents_hub(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@control-acceso.test')->first();

        $response = $this->actingAs($admin)->get(route('admin.documents.index'));

        $response->assertOk();
        $response->assertSee('Expedientes');
        $response->assertSee('Facturas demo');
    }

    public function test_guard_cannot_access_documents(): void
    {
        $this->seed();

        $guard = User::query()->where('email', 'guardia@control-acceso.test')->first();

        $response = $this->actingAs($guard)->get(route('admin.documents.index'));

        $response->assertForbidden();
    }

    public function test_acceptance_and_manual_payment_flow(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@control-acceso.test')->first();
        $company = SecurityCompany::query()->where('tax_id', '900123456-1')->firstOrFail();

        $this->assertFalse($company->hasCompletedAcceptance());

        $acceptanceResponse = $this->actingAs($admin)->post(
            route('admin.documents.expedientes.acceptance', $company),
            [
                'representative_name' => 'Carlos Representante',
                'representative_role' => 'Gerente General',
                'representative_document_type' => 'CC',
                'representative_document_number' => '1234567890',
                'accept_contract' => '1',
                'accept_terms' => '1',
                'accept_privacy' => '1',
            ],
        );

        $acceptanceResponse->assertRedirect(route('admin.documents.expedientes.show', $company));
        $acceptanceResponse->assertSessionHas('success');

        $company->refresh();
        $this->assertTrue($company->hasCompletedAcceptance());

        Storage::fake('local');
        $intent = $company->isUpToDate() ? 'anticipate' : 'renew';

        $paymentResponse = $this->actingAs($admin)->post(
            route('admin.documents.expedientes.payment.manual', $company),
            [
                'reference' => 'TRANS-TEST-001',
                'intent' => $intent,
                'proof' => UploadedFile::fake()->create('comprobante.pdf', 120, 'application/pdf'),
            ],
        );

        $paymentResponse->assertRedirect(route('admin.companies.historial', $company));
        $paymentResponse->assertSessionHas('success');

        $this->assertDatabaseHas('commercial_payments', [
            'security_company_id' => $company->id,
            'reference' => 'TRANS-TEST-001',
        ]);

        $this->assertDatabaseHas('platform_documents', [
            'security_company_id' => $company->id,
            'type' => 'invoice',
            'is_demo' => true,
        ]);
    }

    public function test_publishing_normativa_does_not_alter_frozen_acceptance(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@control-acceso.test')->first();
        $company = SecurityCompany::query()->where('tax_id', '900123456-1')->firstOrFail();

        $this->actingAs($admin)->post(
            route('admin.documents.expedientes.acceptance', $company),
            [
                'representative_name' => 'Carlos Representante',
                'representative_role' => 'Gerente General',
                'representative_document_type' => 'CC',
                'representative_document_number' => '1234567890',
                'accept_contract' => '1',
                'accept_terms' => '1',
                'accept_privacy' => '1',
            ],
        )->assertRedirect();

        $acceptance = $company->fresh()->latestAcceptance();
        $this->assertNotNull($acceptance);
        $frozenTitle = collect($acceptance->corpus_snapshot)->firstWhere('type', 'terms')['title'] ?? null;
        $frozenContent = collect($acceptance->corpus_snapshot)->firstWhere('type', 'terms')['content'] ?? null;
        $this->assertNotEmpty($frozenContent);

        $terms = \App\Models\LegalCorpusVersion::query()
            ->where('type', 'terms')
            ->whereNull('package_sku')
            ->whereNull('superseded_at')
            ->firstOrFail();

        $this->actingAs($admin)->put(
            route('admin.documents.normativa.publish', $terms),
            [
                'title' => 'Términos actualizados post-aceptación',
                'content' => str_repeat('Texto nuevo de términos que no debe aparecer en el expediente congelado. ', 3),
                'effective_from' => now()->toDateString(),
            ],
        )->assertRedirect();

        $acceptance->refresh();
        $this->assertSame($frozenTitle, collect($acceptance->corpus_snapshot)->firstWhere('type', 'terms')['title'] ?? null);
        $this->assertSame($frozenContent, collect($acceptance->corpus_snapshot)->firstWhere('type', 'terms')['content'] ?? null);
        $this->assertDatabaseHas('legal_corpus_versions', [
            'type' => 'terms',
            'version' => '1.1',
            'title' => 'Términos actualizados post-aceptación',
        ]);
    }

    public function test_manual_payment_requires_acceptance(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@control-acceso.test')->first();
        $company = SecurityCompany::query()->where('tax_id', '900123456-1')->firstOrFail();

        $response = $this->actingAs($admin)->post(
            route('admin.documents.expedientes.payment.manual', $company),
            ['reference' => 'SIN-ACEPTACION'],
        );

        $response->assertRedirect(route('admin.documents.expedientes.show', $company));
        $response->assertSessionHas('warning');
    }
}
