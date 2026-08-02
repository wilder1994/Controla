<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\Models\Client;
use App\Models\SecurityCompany;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ScopedUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_admin_can_create_guard_for_own_client(): void
    {
        $this->seed();

        $company = SecurityCompany::query()->where('tax_id', '900123456-1')->firstOrFail();
        $client = Client::query()->where('slug', 'palmas-del-ingenio')->firstOrFail();
        $admin = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();

        $response = $this->actingAs($admin)->post(route('company.users.store'), [
            'name' => 'Guardia Nuevo',
            'email' => 'guardia.nuevo@sj-seguridad.test',
            'password' => 'Guardia123!',
            'password_confirmation' => 'Guardia123!',
            'role' => 'guardia',
            'client_ids' => [$client->id],
            'is_active' => '1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'guardia.nuevo@sj-seguridad.test']);
        $this->assertDatabaseHas('client_user_assignments', [
            'client_id' => $client->id,
            'user_id' => User::query()->where('email', 'guardia.nuevo@sj-seguridad.test')->value('id'),
        ]);
    }

    public function test_company_admin_cannot_edit_super_admin_user(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();
        $super = User::query()->where('email', 'admin@control-acceso.test')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('company.users.edit', $super));

        $response->assertForbidden();
    }

    public function test_client_admin_can_create_resident_for_conjunto(): void
    {
        $this->seed();

        $clientAdmin = User::query()->where('email', 'admin@palmasdelingenio.test')->firstOrFail();

        $response = $this->actingAs($clientAdmin)->post(route('client.users.store'), [
            'name' => 'Residente Nuevo',
            'email' => 'residente.nuevo@palmas.test',
            'password' => 'Residente123!',
            'password_confirmation' => 'Residente123!',
            'role' => 'resident',
            'is_active' => '1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'residente.nuevo@palmas.test']);
    }

    public function test_company_settings_updates_geo_fields(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();

        $response = $this->actingAs($admin)->put(route('company.settings.update'), [
            'legal_name' => 'SJ Seguridad Privada S.A.S.',
            'trade_name' => 'SJ Seguridad',
            'tax_id' => '900123456-1',
            'party_type' => 'legal_entity',
            'email' => 'contacto@sj-seguridad.test',
            'phone' => '+57 300 000 0000',
            'address' => 'Calle 100 # 15-20, Bogotá',
            'latitude' => 4.65,
            'longitude' => -74.05,
        ]);

        $response->assertRedirect(route('company.settings.edit'));
        $this->assertDatabaseHas('security_companies', [
            'tax_id' => '900123456-1',
            'address' => 'Calle 100 # 15-20, Bogotá',
        ]);
    }
}
