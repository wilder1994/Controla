<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\Models\Client;
use App\Models\CompanyCollaboratorType;
use App\Models\CompanyJobTitle;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ScopedUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_admin_can_create_guard_for_own_client(): void
    {
        $this->seedWithPilot();

        $client = Client::query()->where('slug', 'palmas-del-ingenio')->firstOrFail();
        $admin = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();
        $employee = Employee::query()->create([
            'security_company_id' => $admin->security_company_id,
            'job_title_id' => CompanyJobTitle::query()->firstOrCreate(
                ['security_company_id' => $admin->security_company_id, 'name' => 'Portería extra'],
                ['is_active' => true, 'sort_order' => 99],
            )->id,
            'collaborator_type_id' => CompanyCollaboratorType::query()->firstOrCreate(
                ['security_company_id' => $admin->security_company_id, 'name' => 'OPERATIVO'],
                ['is_active' => true, 'sort_order' => 10],
            )->id,
            'document_type' => 'CC',
            'document_number' => '1098000001',
            'last_name_paternal' => 'Nuevo',
            'last_name_maternal' => 'Ficha',
            'first_names' => 'Vigilante',
            'sex' => 'hombre',
            'birth_date' => '1991-01-01',
            'email' => 'vigilante.ficha@sj-seguridad.test',
            'nationality' => 'COLOMBIANA',
            'blood_group' => 'O+',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('company.users.store'), [
            'role' => 'guardia',
            'employee_id' => $employee->id,
            'job_title' => 'Portería extra',
            'username' => 'vigilante.nuevo.1001',
            'password' => 'Guardia123!',
            'password_confirmation' => 'Guardia123!',
            'client_ids' => [$client->id],
            'is_active' => '1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'employee_id' => $employee->id,
            'username' => 'vigilante.nuevo.1001',
            'email' => null,
            'job_title' => 'Portería extra',
        ]);
        $this->assertDatabaseHas('client_user_assignments', [
            'client_id' => $client->id,
            'user_id' => User::query()->where('employee_id', $employee->id)->value('id'),
        ]);
    }

    public function test_company_admin_can_create_supervisor_with_code_without_client(): void
    {
        $this->seedWithPilot();

        $admin = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();
        $employee = Employee::query()->create([
            'security_company_id' => $admin->security_company_id,
            'job_title_id' => CompanyJobTitle::query()->firstOrCreate(
                ['security_company_id' => $admin->security_company_id, 'name' => 'Supervisor extra'],
                ['is_active' => true, 'sort_order' => 98],
            )->id,
            'collaborator_type_id' => CompanyCollaboratorType::query()->firstOrCreate(
                ['security_company_id' => $admin->security_company_id, 'name' => 'OPERATIVO'],
                ['is_active' => true, 'sort_order' => 10],
            )->id,
            'document_type' => 'CC',
            'document_number' => '1098000002',
            'last_name_paternal' => 'Norte',
            'last_name_maternal' => 'Zona',
            'first_names' => 'Supervisor',
            'sex' => 'hombre',
            'birth_date' => '1985-01-01',
            'email' => 'sup.ficha@sj-seguridad.test',
            'nationality' => 'COLOMBIANA',
            'blood_group' => 'O+',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('company.users.store'), [
            'role' => 'supervisor',
            'employee_id' => $employee->id,
            'job_title' => 'Supervisor extra',
            'username' => 'supervisor.norte.4401',
            'password' => 'Super123!',
            'password_confirmation' => 'Super123!',
            'is_active' => '1',
        ]);

        $response->assertRedirect();
        $user = User::query()->where('employee_id', $employee->id)->firstOrFail();
        $this->assertTrue($user->hasRole('supervisor'));
        $this->assertNotNull($user->supervisor_code);
        $this->assertSame(6, strlen($user->supervisor_code));
        $this->assertTrue(ctype_digit($user->supervisor_code));
        $this->assertDatabaseMissing('client_user_assignments', ['user_id' => $user->id]);
    }

    public function test_reassigning_vigilante_requires_new_password(): void
    {
        $this->seedWithPilot();

        $admin = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();
        $palmas = Client::query()->where('slug', 'palmas-del-ingenio')->firstOrFail();
        $torres = Client::query()->where('slug', 'torres-loma')->firstOrFail();
        $vigilante = User::query()->where('email', 'guardia@control-acceso.test')->firstOrFail();

        $denied = $this->actingAs($admin)->put(route('company.users.update', $vigilante), [
            'name' => $vigilante->name,
            'role' => 'guardia',
            'job_title' => $vigilante->job_title,
            'client_ids' => [$torres->id],
            'is_active' => '1',
        ]);

        $denied->assertSessionHasErrors('password');

        $ok = $this->actingAs($admin)->put(route('company.users.update', $vigilante), [
            'name' => $vigilante->name,
            'role' => 'guardia',
            'job_title' => $vigilante->job_title,
            'client_ids' => [$torres->id],
            'password' => 'NuevaClave123!',
            'password_confirmation' => 'NuevaClave123!',
            'is_active' => '1',
        ]);

        $ok->assertRedirect();
        $vigilante->refresh();
        $this->assertSame($torres->id, (int) $vigilante->primary_client_id);
        $this->assertFalse($vigilante->clients()->where('clients.id', $palmas->id)->exists());
    }

    public function test_company_admin_cannot_edit_super_admin_user(): void
    {
        $this->seedWithPilot();

        $admin = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();
        $super = User::query()->where('email', 'admin@control-acceso.test')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('company.users.edit', $super));

        $response->assertForbidden();
    }

    public function test_client_admin_can_create_resident_for_conjunto(): void
    {
        $this->seedWithPilot();

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
        $this->seedWithPilot();

        $admin = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();

        $response = $this->actingAs($admin)->put(route('company.settings.update'), [
            'legal_name' => 'SJ Seguridad Privada S.A.S.',
            'trade_name' => 'SJ Seguridad',
            'tax_id' => '900123456-1',
            'party_type' => 'legal_entity',
            'email' => 'contacto@sj-seguridad.test',
            'phone' => '+57 300 000 0000',
            'address' => 'Calle 100 # 15-20',
            'city' => 'Bogotá',
            'department' => 'Cundinamarca',
            'latitude' => 4.65,
            'longitude' => -74.05,
            'field_sheet_intro' => 'Encabezado de prueba Decreto 356.',
        ]);

        $response->assertRedirect(route('company.settings.edit'));
        $this->assertDatabaseHas('security_companies', [
            'tax_id' => '900123456-1',
            'address' => 'Calle 100 # 15-20',
            'city' => 'Bogotá',
            'department' => 'Cundinamarca',
            'field_sheet_intro' => 'Encabezado de prueba Decreto 356.',
        ]);
    }
}
