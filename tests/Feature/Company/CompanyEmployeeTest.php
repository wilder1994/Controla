<?php

declare(strict_types=1);

namespace Tests\Feature\Company;

use App\Models\CompanyCollaboratorType;
use App\Models\CompanyJobTitle;
use App\Models\Employee;
use App\Models\SecurityCompany;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
        $response->assertSee('Selecciona, pega o arrastra el logo');
        $this->actingAs($admin)->get(route('company.settings.logo'))->assertNotFound();
    }

    public function test_http_419_on_settings_redirects_back_with_flash(): void
    {
        $this->seedWithPilot();
        $admin = $this->companyAdmin();
        $this->actingAs($admin);
        session()->setPreviousUrl(route('company.settings.edit'));

        $request = \Illuminate\Http\Request::create(route('company.settings.update'), 'PUT');
        $request->setLaravelSession($this->app['session']->driver());
        $request->setUserResolver(fn () => $admin);

        $response = $this->app[\Illuminate\Contracts\Debug\ExceptionHandler::class]
            ->render($request, new \Symfony\Component\HttpKernel\Exception\HttpException(419, 'CSRF token mismatch.'));

        $this->assertTrue($response->isRedirect(route('company.settings.edit')));
        $this->assertSame('La página expiró. Recarga e intenta guardar de nuevo.', session('error'));
    }

    public function test_employees_module_has_no_ajustes_tabs(): void
    {
        $this->seedWithPilot();
        $admin = $this->companyAdmin();

        $this->actingAs($admin)
            ->get(route('company.employees.index'))
            ->assertOk()
            ->assertSee('Empleados')
            ->assertSee('Nuevo empleado')
            ->assertSee('Ingreso')
            ->assertSee('Teléfono')
            ->assertSee('EPS')
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
            ->assertSee('Estructuras')
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
        $this->actingAs($guard)->get(route('company.structure-types.index'))->assertForbidden();
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
            'name' => 'ADMINISTRATIVO',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('company.collaborator-types.index'));
        $this->assertDatabaseHas('company_collaborator_types', [
            'security_company_id' => $company->id,
            'name' => 'ADMINISTRATIVO',
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
        $this->assertSame('Antioquia', $employee->birth_department);
        $this->assertSame('Medellín', $employee->birth_city);
        $this->assertSame('3001112233', $employee->phone);
        $this->assertSame('Sura', $employee->eps_name);
        $this->assertSame('2024-01-15', $employee->hired_on?->toDateString());
        $this->assertTrue($employee->is_active);
        $this->assertNull($employee->user);
    }

    public function test_employee_ficha_shows_sj_sig_blocks(): void
    {
        $this->seedWithPilot();
        $admin = $this->companyAdmin();
        $employee = $this->createEmployee($this->createJobTitle('Vigilante'), [
            'phone' => '3001112233',
            'eps_name' => 'Sura',
            'hired_on' => '2024-01-15',
        ]);

        $this->actingAs($admin)
            ->get(route('company.employees.index'))
            ->assertOk()
            ->assertSee('Ficha')
            ->assertSee('3001112233')
            ->assertSee('Sura');

        $this->actingAs($admin)
            ->get(route('company.employees.show', $employee))
            ->assertOk()
            ->assertSee('Identidad')
            ->assertSee('Contacto y residencia')
            ->assertSee('Vinculación laboral')
            ->assertSee('Seguridad social')
            ->assertSee('Sura')
            ->assertSee('carpeta documental');
    }

    public function test_employee_photo_can_be_uploaded(): void
    {
        Storage::fake('local');
        $this->seedWithPilot();
        $admin = $this->companyAdmin();
        $employee = $this->createEmployee($this->createJobTitle('Vigilante'));

        $this->actingAs($admin)->post(route('company.employees.photo.store', $employee), [
            'photo' => UploadedFile::fake()->image('foto.jpg', 200, 200),
        ])->assertRedirect(route('company.employees.show', $employee));

        $employee->refresh();
        $this->assertNotNull($employee->photo_path);
        Storage::disk('local')->assertExists($employee->photo_path);

        $this->actingAs($admin)
            ->get(route('company.employees.photo', $employee))
            ->assertOk();
    }

    public function test_employee_rejects_municipality_outside_department(): void
    {
        $this->seedWithPilot();
        $admin = $this->companyAdmin();
        $title = $this->createJobTitle('Vigilante');

        $this->actingAs($admin)->from(route('company.employees.create'))->post(
            route('company.employees.store'),
            $this->employeePayload($title, [
                'birth_department' => 'Antioquia',
                'birth_city' => 'Cali',
            ]),
        )->assertSessionHasErrors('birth_city');
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

        $this->assertDatabaseMissing('employees', ['document_number' => '1098765432']);
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

    public function test_employee_edit_form_renders(): void
    {
        $this->seedWithPilot();
        $admin = $this->companyAdmin();
        $employee = $this->createEmployee($this->createJobTitle('Supervisor de zona'));

        $this->actingAs($admin)
            ->get(route('company.employees.edit', $employee))
            ->assertOk()
            ->assertSee('Pérez');
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
        return CompanyJobTitle::query()->firstOrCreate(
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
            'birth_department' => 'Antioquia',
            'birth_city' => 'Medellín',
            'document_issue_department' => 'Antioquia',
            'document_issue_city' => 'Medellín',
            'phone' => '3001112233',
            'residence_city' => 'Cali',
            'address' => 'Calle 1 # 2-3',
            'education' => 'Bachiller',
            'marital_status' => 'Soltero',
            'children_count' => '0',
            'engagement_type' => 'Término fijo',
            'contributor_type' => 'Dependiente',
            'labor_contract_type' => 'Laboral',
            'hired_on' => '2024-01-15',
            'eps_code' => 'EPS001',
            'eps_name' => 'Sura',
            'afp_name' => 'Porvenir',
            'compensation_fund' => 'Comfandi',
            'arl_name' => 'Sura ARL',
            'arl_risk_level' => 'IV',
        ], $overrides);
    }
}
