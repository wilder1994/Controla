<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\Models\Client;
use App\Models\CompanyCollaboratorType;
use App\Models\CompanyJobTitle;
use App\Models\Employee;
use App\Models\Installation;
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

        $this->assignEmployeeToClientPost($employee, $client);

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

    public function test_client_admin_can_create_another_client_admin(): void
    {
        $this->seedWithPilot();

        $clientAdmin = User::query()->where('email', 'admin@palmasdelingenio.test')->firstOrFail();

        $response = $this->actingAs($clientAdmin)->post(route('client.users.store'), [
            'name' => 'Admin Extra Palmas',
            'document_number' => '1098123456',
            'job_title' => 'Administrador',
            'email' => 'admin.extra@palmas.test',
            'password' => 'Cliente123!',
            'password_confirmation' => 'Cliente123!',
            'role' => 'client-admin',
            'is_active' => '1',
        ]);

        $response->assertRedirect();
        $created = User::query()->where('email', 'admin.extra@palmas.test')->firstOrFail();
        $this->assertTrue($created->hasRole('client-admin'));
        $this->assertSame('external', $created->admin_origin);
        $this->assertSame('1098123456', $created->document_number);
        $this->assertTrue($created->must_change_password);
        $this->assertTrue($created->clients()->where('clients.id', $clientAdmin->primary_client_id)->exists());
    }

    public function test_client_admin_cannot_create_vigilante(): void
    {
        $this->seedWithPilot();

        $clientAdmin = User::query()->where('email', 'admin@palmasdelingenio.test')->firstOrFail();

        $response = $this->actingAs($clientAdmin)
            ->from(route('client.users.create'))
            ->post(route('client.users.store'), [
                'name' => 'Vigilante Intruso',
                'email' => 'vigilante.intruso@palmas.test',
                'password' => 'Guardia123!',
                'password_confirmation' => 'Guardia123!',
                'role' => 'guardia',
                'is_active' => '1',
            ]);

        $response->assertSessionHasErrors('role');
    }

    public function test_company_can_assign_internal_client_admin_to_several_clients(): void
    {
        $this->seedWithPilot();

        $admin = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();
        $palmas = Client::query()->where('slug', 'palmas-del-ingenio')->firstOrFail();
        $torres = Client::query()->where('slug', 'torres-loma')->firstOrFail();
        $employee = Employee::query()->create([
            'security_company_id' => $admin->security_company_id,
            'job_title_id' => CompanyJobTitle::query()->firstOrCreate(
                ['security_company_id' => $admin->security_company_id, 'name' => 'Coordinador cliente'],
                ['is_active' => true, 'sort_order' => 40],
            )->id,
            'collaborator_type_id' => CompanyCollaboratorType::query()->firstOrCreate(
                ['security_company_id' => $admin->security_company_id, 'name' => 'ADMINISTRATIVO'],
                ['is_active' => true, 'sort_order' => 5],
            )->id,
            'document_type' => 'CC',
            'document_number' => '1098000099',
            'last_name_paternal' => 'Interno',
            'last_name_maternal' => 'Admin',
            'first_names' => 'Carlos',
            'sex' => 'hombre',
            'birth_date' => '1988-01-01',
            'email' => 'carlos.interno@sj-seguridad.test',
            'nationality' => 'COLOMBIANA',
            'blood_group' => 'O+',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('company.users.store'), [
            'role' => 'client-admin',
            'origin' => 'internal',
            'employee_id' => $employee->id,
            'job_title' => 'Coordinador cliente',
            'username' => 'carlos.interno.1099',
            'password' => 'Cliente123!',
            'password_confirmation' => 'Cliente123!',
            'client_ids' => [$palmas->id, $torres->id],
            'is_active' => '1',
        ]);

        $response->assertRedirect();
        $created = User::query()->where('employee_id', $employee->id)->firstOrFail();
        $this->assertTrue($created->hasRole('client-admin'));
        $this->assertSame('internal', $created->admin_origin);
        $this->assertEqualsCanonicalizing([$palmas->id, $torres->id], $created->clients()->pluck('clients.id')->all());
    }

    public function test_company_can_create_external_installation_admin(): void
    {
        $this->seedWithPilot();

        $admin = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();
        $palmas = Client::query()->where('slug', 'palmas-del-ingenio')->firstOrFail();
        $site = Installation::query()->where('client_id', $palmas->id)->firstOrFail();
        $extra = Installation::query()->create([
            'client_id' => $palmas->id,
            'name' => 'Sede norte',
            'is_client_site' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('company.users.store'), [
            'role' => 'client-installation-admin',
            'origin' => 'external',
            'name' => 'Laura Sede',
            'document_number' => '1098000088',
            'job_title' => 'Administradora de sede',
            'email' => 'laura.sede@palmas.test',
            'username' => 'laura.sede.8800',
            'password' => 'Cliente123!',
            'password_confirmation' => 'Cliente123!',
            'client_ids' => [$palmas->id],
            'installation_ids' => [$site->id, $extra->id],
            'is_active' => '1',
        ]);

        $response->assertRedirect();
        $created = User::query()->where('email', 'laura.sede@palmas.test')->firstOrFail();
        $this->assertTrue($created->hasRole('client-installation-admin'));
        $this->assertSame('external', $created->admin_origin);
        $this->assertEqualsCanonicalizing([$site->id, $extra->id], $created->assignedInstallations()->pluck('installations.id')->all());
        $this->assertFalse($created->can('client.users.manage'));
        $this->assertFalse($created->can('client.settings.manage'));

        $created->update(['must_change_password' => false]);

        $this->actingAs($created)
            ->withSession(['tenancy.active_client_id' => $palmas->id])
            ->get(route('client.users.index'))
            ->assertForbidden();

        $this->actingAs($created)
            ->withSession(['tenancy.active_client_id' => $palmas->id])
            ->get(route('client.settings.member-types.index'))
            ->assertOk();

        $this->actingAs($created)
            ->withSession(['tenancy.active_client_id' => $palmas->id])
            ->post(route('client.settings.member-types.store'), ['name' => 'No debe', 'is_active' => true])
            ->assertForbidden();
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
