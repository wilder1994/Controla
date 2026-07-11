<?php

declare(strict_types=1);

namespace Tests\Feature\Company;

use App\Models\Client;
use App\Models\SecurityCompany;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CompanyClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_admin_can_create_client_without_super_admin(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'empresa@sj-seguridad.test')->first();
        $this->assertNotNull($admin);

        $response = $this->actingAs($admin)->post(route('company.clients.store'), [
            'name' => 'Conjunto Nuevo Piloto',
            'slug' => 'conjunto-nuevo-piloto',
            'login_suffix' => 'conjuntopiloto',
            'plan_tier' => 'economic',
            'is_active' => true,
        ]);

        $response->assertRedirect();

        $client = Client::query()->where('slug', 'conjunto-nuevo-piloto')->first();
        $this->assertNotNull($client);
        $this->assertSame((int) $admin->security_company_id, (int) $client->security_company_id);
    }

    public function test_company_admin_can_assign_operatives_to_client(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'empresa@sj-seguridad.test')->first();
        $client = Client::query()->where('slug', 'torres-loma')->first();
        $supervisor = User::query()->where('email', 'supervisor@sj-seguridad.test')->first();

        $this->assertNotNull($admin);
        $this->assertNotNull($client);
        $this->assertNotNull($supervisor);

        $response = $this->actingAs($admin)->post(route('company.clients.assign', $client), [
            'user_ids' => [$supervisor->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertTrue(
            $client->users()->where('users.id', $supervisor->id)->exists()
        );
    }
}
