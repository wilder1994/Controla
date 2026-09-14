<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\Models\Client;
use App\Models\CompanyCollaboratorType;
use App\Models\CompanyJobTitle;
use App\Models\Employee;
use App\Models\Installation;
use App\Models\ObservatoryEvent;
use App\Models\User;
use App\Enums\BloodGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CollaboratorAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_admin_can_create_collaborator_with_observatory_view(): void
    {
        $this->seedWithPilot();
        $admin = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();
        $employee = $this->makeEmployee('1098000111');

        $preview = $this->actingAs($admin)->postJson(route('company.users.credentials-preview'), [
            'employee_id' => $employee->id,
        ]);
        $username = $preview->json('username');
        $password = $preview->json('password');

        $this->actingAs($admin)->post(route('company.users.store'), [
            'role' => 'colaborador',
            'employee_id' => $employee->id,
            'job_title' => $employee->jobTitle?->name,
            'username' => $username,
            'password' => $password,
            'password_confirmation' => $password,
            'grants' => [
                'company' => [
                    'observatory' => 'view',
                    'clients' => 'none',
                    'users' => 'none',
                ],
            ],
        ])->assertRedirect();

        $user = User::query()->where('employee_id', $employee->id)->firstOrFail();
        $this->assertTrue($user->hasRole('colaborador'));
        $this->assertTrue($user->can('observatory.view'));
        $this->assertFalse($user->can('company.billing.manage'));
        $this->assertFalse($user->can('company.users.assign'));
    }

    public function test_collaborator_is_blocked_from_billing_and_allowed_on_observatory(): void
    {
        $this->seedWithPilot();
        $admin = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();
        $employee = $this->makeEmployee('1098000222');
        $user = app(\App\Services\Company\GrantEmployeeAccessService::class)->execute(
            $employee,
            $admin,
            'colaborador',
            'Clave1234!',
            [],
            'ana.lopes.2200',
            $employee->jobTitle?->name,
            app(\App\Services\User\ParseAccessGrants::class)->fromInput([
                'company' => ['observatory' => 'view'],
            ], (int) $employee->security_company_id),
        );
        $user->update(['must_change_password' => false]);

        $this->actingAs($user)
            ->get(route('company.observatory.events.index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('company.billing.index'))
            ->assertForbidden();
    }

    public function test_collaborator_cannot_grant_more_than_own_matrix(): void
    {
        $this->seedWithPilot();
        $admin = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();
        $limitedEmployee = $this->makeEmployee('1098000333');
        $limited = app(\App\Services\Company\GrantEmployeeAccessService::class)->execute(
            $limitedEmployee,
            $admin,
            'colaborador',
            'Clave1234!',
            [],
            'ana.lopes.3300',
            $limitedEmployee->jobTitle?->name,
            app(\App\Services\User\ParseAccessGrants::class)->fromInput([
                'company' => ['observatory' => 'view', 'users' => 'manage'],
            ], (int) $limitedEmployee->security_company_id),
        );
        $limited->update(['must_change_password' => false]);

        $target = $this->makeEmployee('1098000444');
        $preview = $this->actingAs($limited)->postJson(route('company.users.credentials-preview'), [
            'employee_id' => $target->id,
        ]);

        $this->actingAs($limited)->from(route('company.users.create'))->post(route('company.users.store'), [
            'role' => 'colaborador',
            'employee_id' => $target->id,
            'job_title' => $target->jobTitle?->name,
            'username' => $preview->json('username'),
            'password' => $preview->json('password'),
            'password_confirmation' => $preview->json('password'),
            'grants' => [
                'company' => [
                    'users' => 'manage',
                    'observatory' => 'manage',
                ],
            ],
        ])->assertSessionHasErrors('grants');
    }

    public function test_collaborator_cannot_create_company_admin(): void
    {
        $this->seedWithPilot();
        $admin = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();
        $limitedEmployee = $this->makeEmployee('1098000555');
        $limited = app(\App\Services\Company\GrantEmployeeAccessService::class)->execute(
            $limitedEmployee,
            $admin,
            'colaborador',
            'Clave1234!',
            [],
            'ana.lopes.5500',
            $limitedEmployee->jobTitle?->name,
            app(\App\Services\User\ParseAccessGrants::class)->fromInput([
                'company' => ['users' => 'manage'],
            ], (int) $limitedEmployee->security_company_id),
        );
        $limited->update(['must_change_password' => false]);

        $target = $this->makeEmployee('1098000666');
        $preview = $this->actingAs($limited)->postJson(route('company.users.credentials-preview'), [
            'employee_id' => $target->id,
        ]);

        $this->actingAs($limited)->from(route('company.users.create'))->post(route('company.users.store'), [
            'role' => 'company-admin',
            'employee_id' => $target->id,
            'job_title' => $target->jobTitle?->name,
            'username' => $preview->json('username'),
            'password' => $preview->json('password'),
            'password_confirmation' => $preview->json('password'),
        ])->assertSessionHasErrors('role');

        $this->assertNull(User::query()->where('employee_id', $target->id)->first());
    }

    public function test_company_observatory_grant_opens_event_detail(): void
    {
        $this->seedWithPilot();
        $admin = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();
        $employee = $this->makeEmployee('1098000777');
        $user = app(\App\Services\Company\GrantEmployeeAccessService::class)->execute(
            $employee,
            $admin,
            'colaborador',
            'Clave1234!',
            [],
            'ana.lopes.7700',
            $employee->jobTitle?->name,
            app(\App\Services\User\ParseAccessGrants::class)->fromInput([
                'company' => ['observatory' => 'view'],
            ], (int) $employee->security_company_id),
        );
        $user->update(['must_change_password' => false]);

        $client = Client::query()->where('security_company_id', $employee->security_company_id)->firstOrFail();
        $installation = Installation::query()->where('client_id', $client->id)->firstOrFail();
        $event = ObservatoryEvent::query()->create([
            'client_id' => $client->id,
            'installation_id' => $installation->id,
            'status' => 'nuevo',
            'title' => 'Novedad de prueba',
            'opened_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('company.observatory.events.show', $event))
            ->assertOk()
            ->assertSee($event->folio(), false);
    }

    public function test_employees_grant_opens_employee_list(): void
    {
        $this->seedWithPilot();
        $admin = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();
        $employee = $this->makeEmployee('1098000888');
        $user = app(\App\Services\Company\GrantEmployeeAccessService::class)->execute(
            $employee,
            $admin,
            'colaborador',
            'Clave1234!',
            [],
            'ana.lopes.8800',
            $employee->jobTitle?->name,
            app(\App\Services\User\ParseAccessGrants::class)->fromInput([
                'company' => ['employees' => 'view'],
            ], (int) $employee->security_company_id),
        );
        $user->update(['must_change_password' => false]);

        $this->actingAs($user)
            ->get(route('company.employees.index'))
            ->assertOk();
    }

    public function test_documents_manage_can_open_folder(): void
    {
        $this->seedWithPilot();
        $admin = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();
        $employee = $this->makeEmployee('1098000999');
        $user = app(\App\Services\Company\GrantEmployeeAccessService::class)->execute(
            $employee,
            $admin,
            'colaborador',
            'Clave1234!',
            [],
            'ana.lopes.9900',
            $employee->jobTitle?->name,
            app(\App\Services\User\ParseAccessGrants::class)->fromInput([
                'company' => ['documents' => 'manage'],
            ], (int) $employee->security_company_id),
        );
        $user->update(['must_change_password' => false]);

        $this->actingAs($user)
            ->get(route('company.personnel-documents.index'))
            ->assertOk();

        $this->assertTrue($user->can('company.documents.manage'));
    }

    private function makeEmployee(string $document): Employee
    {
        $companyId = $this->pilotCompanyId();
        $title = CompanyJobTitle::query()->firstOrCreate(
            ['security_company_id' => $companyId, 'name' => 'Analista'],
            ['is_active' => true, 'sort_order' => 20],
        );
        $type = CompanyCollaboratorType::query()->firstOrCreate(
            ['security_company_id' => $companyId, 'name' => 'OPERATIVO'],
            ['is_active' => true, 'sort_order' => 10],
        );

        return Employee::query()->create([
            'security_company_id' => $companyId,
            'job_title_id' => $title->id,
            'collaborator_type_id' => $type->id,
            'document_type' => 'CC',
            'document_number' => $document,
            'last_name_paternal' => 'Lopes',
            'last_name_maternal' => '',
            'first_names' => 'Ana',
            'sex' => 'mujer',
            'birth_date' => '1990-01-01',
            'email' => $document.'@sj-seguridad.test',
            'nationality' => 'COLOMBIANA',
            'blood_group' => BloodGroup::OPositive,
            'is_active' => true,
        ]);
    }
}
