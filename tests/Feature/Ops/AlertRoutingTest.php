<?php

declare(strict_types=1);

namespace Tests\Feature\Ops;

use App\Enums\BloodGroup;
use App\Enums\OperationalAlertType;
use App\Models\Client;
use App\Models\CompanyCollaboratorType;
use App\Models\CompanyJobTitle;
use App\Models\Employee;
use App\Models\OperationalAlert;
use App\Models\User;
use App\Services\Company\GrantEmployeeAccessService;
use App\Services\User\ParseAccessGrants;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AlertRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_observatory_only_does_not_hear_panic(): void
    {
        [$admin, $user] = $this->collaboratorWith(['observatory' => 'view']);
        $this->actingAs($admin)->postJson(route('company.ops.panic'), ['note' => 'X'])->assertCreated();

        $this->actingAs($user)
            ->getJson(route('company.ops.alerts'))
            ->assertOk()
            ->assertJsonCount(0, 'alerts');
    }

    public function test_panic_only_hears_panic_and_can_attend_not_observatory(): void
    {
        [$admin, $user] = $this->collaboratorWith(['panics' => 'manage']);
        $this->actingAs($admin)->postJson(route('company.ops.panic'), ['note' => 'X'])->assertCreated();

        $this->actingAs($user)
            ->getJson(route('company.ops.alerts'))
            ->assertOk()
            ->assertJsonPath('alerts.0.type', 'panic')
            ->assertJsonPath('alerts.0.can_attend', true);

        $client = Client::query()->where('security_company_id', $admin->security_company_id)->firstOrFail();
        OperationalAlert::query()->create([
            'type' => OperationalAlertType::Observatory,
            'security_company_id' => $admin->security_company_id,
            'actor_user_id' => $admin->id,
            'client_id' => $client->id,
            'title' => 'Reporte Observatorio',
            'body' => 'Nuevo reporte',
        ]);

        $types = collect($this->actingAs($user)->getJson(route('company.ops.alerts'))->json('alerts'))
            ->pluck('type')
            ->all();
        $this->assertContains('panic', $types);
        $this->assertNotContains('observatory', $types);
    }

    public function test_supervision_hears_panic_but_cannot_attend(): void
    {
        [$admin, $user] = $this->collaboratorWith(['supervision' => 'view']);
        $this->actingAs($admin)->postJson(route('company.ops.panic'), ['note' => 'X'])->assertCreated();

        $this->actingAs($user)
            ->getJson(route('company.ops.alerts'))
            ->assertOk()
            ->assertJsonPath('alerts.0.type', 'panic')
            ->assertJsonPath('alerts.0.can_attend', false);
    }

    public function test_panic_permission_saves_without_supervision(): void
    {
        $this->seedWithPilot();
        $admin = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();
        $employee = $this->opsEmployee('1098002111');
        $preview = $this->actingAs($admin)->postJson(route('company.users.credentials-preview'), [
            'employee_id' => $employee->id,
        ]);

        $this->actingAs($admin)->post(route('company.users.store'), [
            'role' => 'colaborador',
            'employee_id' => $employee->id,
            'job_title' => $employee->jobTitle?->name,
            'username' => $preview->json('username'),
            'password' => $preview->json('password'),
            'password_confirmation' => $preview->json('password'),
            'grants' => [
                'company' => [
                    'panics' => 'manage',
                ],
            ],
        ])->assertRedirect();

        $user = User::query()->where('employee_id', $employee->id)->firstOrFail();
        $this->assertTrue($user->can('ops.panic.attend'));
        $this->assertFalse($user->can('company.supervision.view'));
        $this->assertFalse($user->can('observatory.view'));
    }

    /** @return array{0: User, 1: User} */
    private function collaboratorWith(array $companyGrants): array
    {
        $this->seedWithPilot();
        $admin = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();
        $employee = $this->opsEmployee('1098'.random_int(100000, 999999));
        $user = app(GrantEmployeeAccessService::class)->execute(
            $employee,
            $admin,
            'colaborador',
            'Clave1234!',
            [],
            'ana.'.substr((string) $employee->document_number, -4),
            $employee->jobTitle?->name,
            app(ParseAccessGrants::class)->fromInput(
                ['company' => $companyGrants],
                (int) $employee->security_company_id,
            ),
        );
        $user->update(['must_change_password' => false]);

        return [$admin, $user];
    }

    private function opsEmployee(string $document): Employee
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
