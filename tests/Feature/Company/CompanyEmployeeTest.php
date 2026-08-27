<?php

declare(strict_types=1);

namespace Tests\Feature\Company;

use App\Models\Client;
use App\Models\CompanyCollaboratorType;
use App\Models\CompanyJobTitle;
use App\Models\Employee;
use App\Models\SecurityCompany;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CompanyEmployeeTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_admin_sees_mis_datos_without_ajustes_tabs(): void
    {
        $this->seedWithPilot();
        $admin = $this->companyAdmin();

        $response = $this->actingAs($admin)->get(route('company.settings.edit'));

        $response->assertOk();
        $response->assertSee('Mis datos');
        $response->assertDontSee('admin-header-tab');
    }

    public function test_employees_module_has_no_ajustes_tabs(): void
    {
        $this->seedWithPilot();
        $admin = $this->companyAdmin();

        $this->actingAs($admin)
            ->get(route('company.employees.index'))
            ->assertOk()
            ->assertSee('Empleados')
            ->assertDontSee('admin-header-tab');
    }

    public function test_ajustes_shows_cargos_and_tipos_tabs(): void
    {
        $this->seedWithPilot();
        $admin = $this->companyAdmin();

        $this->actingAs($admin)
            ->get(route('company.job-titles.index'))
            ->assertOk()
            ->assertSee('Ajustes')
            ->assertSee('Cargos')
            ->assertSee('Tipos')
            ->assertSee('Zonas')
            ->assertSee('Turnos')
            ->assertSee('Preoperacional')
            ->assertSee('Documentos')
            ->assertSee('Libros')
            ->assertSee('Tipos de arma')
            ->assertSee('Marcas')
            ->assertSee('admin-header-tab');
    }

    public function test_guard_cannot_access_employees(): void
    {
        $this->seedWithPilot();
        $guard = User::query()->where('email', 'guardia@control-acceso.test')->firstOrFail();

        $this->actingAs($guard)->get(route('company.employees.index'))->assertForbidden();
        $this->actingAs($guard)->get(route('company.job-titles.index'))->assertForbidden();
        $this->actingAs($guard)->get(route('company.collaborator-types.index'))->assertForbidden();
    }

    public function test_company_admin_can_create_job_title(): void
    {
        $this->seedWithPilot();
        $admin = $this->companyAdmin();
        $company = $this->company();

        $response = $this->actingAs($admin)->post(route('company.job-titles.store'), [
            'name' => 'Vigilante de portería',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('company.job-titles.index'));
        $this->assertDatabaseHas('company_job_titles', [
            'security_company_id' => $company->id,
            'name' => 'Vigilante de portería',
            'is_active' => 1,
        ]);
    }

    public function test_job_title_name_must_be_unique_per_company(): void
    {
        $this->seedWithPilot();
        $admin = $this->companyAdmin();
        $this->createJobTitle('Supervisor');

        $response = $this->actingAs($admin)->from(route('company.job-titles.index'))->post(route('company.job-titles.store'), [
            'name' => 'Supervisor',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('company.job-titles.index'));
        $response->assertSessionHasErrors('name');
        $this->assertSame(1, CompanyJobTitle::query()->where('name', 'Supervisor')->count());
    }

    public function test_cannot_delete_job_title_with_employees(): void
    {
        $this->seedWithPilot();
        $admin = $this->companyAdmin();
        $title = $this->createJobTitle('Escolta');
        $this->createEmployee($title);

        $response = $this->actingAs($admin)->from(route('company.job-titles.index'))->delete(route('company.job-titles.destroy', $title));

        $response->assertRedirect(route('company.job-titles.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('company_job_titles', ['id' => $title->id]);
    }

    public function test_company_admin_can_create_collaborator_type(): void
    {
        $this->seedWithPilot();
        $admin = $this->companyAdmin();
        $company = $this->company();

        $response = $this->actingAs($admin)->post(route('company.collaborator-types.store'), [
            'name' => 'OPERATIVO',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('company.collaborator-types.index'));
        $this->assertDatabaseHas('company_collaborator_types', [
            'security_company_id' => $company->id,
            'name' => 'OPERATIVO',
            'is_active' => 1,
        ]);
    }

    public function test_collaborator_type_name_must_be_unique_per_company(): void
    {
        $this->seedWithPilot();
        $admin = $this->companyAdmin();
        $this->createCollaboratorType('ADMINISTRATIVO');

        $response = $this->actingAs($admin)->from(route('company.collaborator-types.index'))->post(route('company.collaborator-types.store'), [
            'name' => 'ADMINISTRATIVO',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('company.collaborator-types.index'));
        $response->assertSessionHasErrors('name');
        $this->assertSame(1, CompanyCollaboratorType::query()->where('name', 'ADMINISTRATIVO')->count());
    }

    public function test_cannot_delete_collaborator_type_with_employees(): void
    {
        $this->seedWithPilot();
        $admin = $this->companyAdmin();
        $title = $this->createJobTitle('Escolta');
        $type = $this->createCollaboratorType('OPERATIVO');
        $this->createEmployee($title, ['collaborator_type_id' => $type->id]);

        $response = $this->actingAs($admin)
            ->from(route('company.collaborator-types.index'))
            ->delete(route('company.collaborator-types.destroy', $type));

        $response->assertRedirect(route('company.collaborator-types.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('company_collaborator_types', ['id' => $type->id]);
    }

    public function test_company_admin_can_create_employee(): void
    {
        $this->seedWithPilot();
        $admin = $this->companyAdmin();
        $title = $this->createJobTitle('Vigilante');

        $response = $this->actingAs($admin)->post(route('company.employees.store'), $this->employeePayload($title));

        $employee = Employee::query()->where('document_number', '1098765432')->firstOrFail();
        $response->assertRedirect(route('company.employees.show', $employee));
        $this->assertSame('Ana', $employee->first_names);
        $this->assertTrue($employee->is_active);
        $this->assertNull($employee->user);
    }

    public function test_employee_can_be_created_with_only_one_last_name(): void
    {
        $this->seedWithPilot();
        $admin = $this->companyAdmin();
        $title = $this->createJobTitle('Vigilante');

        $this->actingAs($admin)->post(route('company.employees.store'), $this->employeePayload($title, [
            'last_name_maternal' => '',
        ]))->assertRedirect();

        $this->assertDatabaseHas('employees', [
            'document_number' => '1098765432',
            'last_name_paternal' => 'Pérez',
            'last_name_maternal' => '',
        ]);
    }

    public function test_employee_requires_at_least_one_last_name(): void
    {
        $this->seedWithPilot();
        $admin = $this->companyAdmin();
        $title = $this->createJobTitle('Vigilante');

        $this->actingAs($admin)->from(route('company.employees.create'))->post(
            route('company.employees.store'),
            $this->employeePayload($title, [
                'last_name_paternal' => '',
                'last_name_maternal' => '',
            ]),
        )->assertSessionHasErrors('last_name_paternal');

        $this->assertSame(0, Employee::query()->count());
    }

    public function test_employee_document_must_be_unique_in_company(): void
    {
        $this->seedWithPilot();
        $admin = $this->companyAdmin();
        $title = $this->createJobTitle('Vigilante');
        $this->createEmployee($title, ['document_number' => '1098765432']);

        $response = $this->actingAs($admin)->from(route('company.employees.create'))->post(
            route('company.employees.store'),
            $this->employeePayload($title, [
                'email' => 'otra.ana@sj-seguridad.test',
            ]),
        );

        $response->assertRedirect(route('company.employees.create'));
        $response->assertSessionHasErrors('document_number');
    }

    public function test_can_archive_and_restore_employee(): void
    {
        $this->seedWithPilot();
        $admin = $this->companyAdmin();
        $employee = $this->createEmployee($this->createJobTitle('Vigilante'));

        $this->actingAs($admin)
            ->post(route('company.employees.archive', $employee))
            ->assertRedirect(route('company.employees.show', $employee));

        $employee->refresh();
        $this->assertFalse($employee->is_active);
        $this->assertNotNull($employee->ceased_at);

        $this->actingAs($admin)
            ->post(route('company.employees.restore', $employee))
            ->assertRedirect(route('company.employees.show', $employee));

        $employee->refresh();
        $this->assertTrue($employee->is_active);
        $this->assertNull($employee->ceased_at);
    }

    public function test_can_grant_supervisor_access_from_employee(): void
    {
        $this->seedWithPilot();
        $admin = $this->companyAdmin();
        $employee = $this->createEmployee($this->createJobTitle('Supervisor de zona'));

        $response = $this->actingAs($admin)->post(route('company.employees.access', $employee), [
            'role' => 'supervisor',
            'password' => 'Clave123!',
            'password_confirmation' => 'Clave123!',
        ]);

        $response->assertRedirect(route('company.employees.show', $employee));
        $user = User::query()->where('email', $employee->email)->firstOrFail();
        $this->assertTrue($user->hasRole('supervisor'));
        $this->assertSame($employee->id, (int) $user->employee_id);
        $this->assertSame('Supervisor de zona', $user->job_title);
        $this->assertNotNull($user->supervisor_code);
        $this->assertDatabaseMissing('client_user_assignments', ['user_id' => $user->id]);
    }

    public function test_grant_guard_access_requires_client(): void
    {
        $this->seedWithPilot();
        $admin = $this->companyAdmin();
        $employee = $this->createEmployee($this->createJobTitle('Portería'));

        $this->actingAs($admin)->from(route('company.employees.show', $employee))->post(
            route('company.employees.access', $employee),
            [
                'role' => 'guardia',
                'password' => 'Clave123!',
                'password_confirmation' => 'Clave123!',
            ],
        )->assertSessionHasErrors('client_ids');

        $client = Client::query()->where('slug', 'palmas-del-ingenio')->firstOrFail();

        $this->actingAs($admin)->post(route('company.employees.access', $employee), [
            'role' => 'guardia',
            'password' => 'Clave123!',
            'password_confirmation' => 'Clave123!',
            'client_ids' => [$client->id],
        ])->assertRedirect(route('company.employees.show', $employee));

        $user = User::query()->where('email', $employee->email)->firstOrFail();
        $this->assertTrue($user->hasRole('guardia'));
        $this->assertDatabaseHas('client_user_assignments', [
            'user_id' => $user->id,
            'client_id' => $client->id,
        ]);
    }

    public function test_cannot_grant_client_admin_from_employee(): void
    {
        $this->seedWithPilot();
        $admin = $this->companyAdmin();
        $employee = $this->createEmployee($this->createJobTitle('Admin conjunto'));
        $client = Client::query()->where('slug', 'palmas-del-ingenio')->firstOrFail();

        $this->actingAs($admin)->from(route('company.employees.show', $employee))->post(
            route('company.employees.access', $employee),
            [
                'role' => 'client-admin',
                'password' => 'Clave123!',
                'password_confirmation' => 'Clave123!',
                'client_ids' => [$client->id],
            ],
        )->assertSessionHasErrors('role');
    }

    public function test_cannot_grant_access_twice(): void
    {
        $this->seedWithPilot();
        $admin = $this->companyAdmin();
        $employee = $this->createEmployee($this->createJobTitle('Supervisor de zona'));

        $this->actingAs($admin)->post(route('company.employees.access', $employee), [
            'role' => 'supervisor',
            'password' => 'Clave123!',
            'password_confirmation' => 'Clave123!',
        ])->assertRedirect();

        $this->actingAs($admin)->from(route('company.employees.show', $employee))->post(
            route('company.employees.access', $employee),
            [
                'role' => 'supervisor',
                'password' => 'Clave123!',
                'password_confirmation' => 'Clave123!',
            ],
        )->assertSessionHasErrors('role');
    }

    private function companyAdmin(): User
    {
        return User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();
    }

    private function company(): SecurityCompany
    {
        return SecurityCompany::query()->where('tax_id', '900123456-1')->firstOrFail();
    }

    private function createJobTitle(string $name): CompanyJobTitle
    {
        return CompanyJobTitle::query()->create([
            'security_company_id' => $this->company()->id,
            'name' => $name,
            'is_active' => true,
            'sort_order' => 10,
        ]);
    }

    private function createCollaboratorType(string $name = 'OPERATIVO'): CompanyCollaboratorType
    {
        return CompanyCollaboratorType::query()->firstOrCreate(
            [
                'security_company_id' => $this->company()->id,
                'name' => $name,
            ],
            [
                'is_active' => true,
                'sort_order' => 10,
            ],
        );
    }

    /** @param array<string, mixed> $overrides */
    private function createEmployee(CompanyJobTitle $title, array $overrides = []): Employee
    {
        if (! array_key_exists('collaborator_type_id', $overrides)) {
            $overrides['collaborator_type_id'] = $this->createCollaboratorType()->id;
        }

        return Employee::query()->create(array_merge([
            'security_company_id' => $this->company()->id,
            'job_title_id' => $title->id,
            'document_type' => 'CC',
            'document_number' => '1098765432',
            'last_name_paternal' => 'Pérez',
            'last_name_maternal' => 'Gómez',
            'first_names' => 'Ana',
            'sex' => 'hombre',
            'birth_date' => '1990-05-12',
            'email' => 'ana.perez@sj-seguridad.test',
            'nationality' => 'COLOMBIANA',
            'blood_group' => 'O+',
            'is_active' => true,
        ], $overrides));
    }

    /** @param array<string, mixed> $overrides */
    private function employeePayload(CompanyJobTitle $title, array $overrides = []): array
    {
        if (! array_key_exists('collaborator_type_id', $overrides)) {
            $overrides['collaborator_type_id'] = $this->createCollaboratorType()->id;
        }

        return array_merge([
            'document_type' => 'CC',
            'document_number' => '1098765432',
            'last_name_paternal' => 'Pérez',
            'last_name_maternal' => 'Gómez',
            'first_names' => 'Ana',
            'sex' => 'mujer',
            'birth_date' => '1990-05-12',
            'job_title_id' => $title->id,
            'email' => 'ana.perez@sj-seguridad.test',
            'nationality' => 'COLOMBIANA',
            'blood_group' => 'O+',
            'has_disability' => '0',
        ], $overrides);
    }
}
