<?php

declare(strict_types=1);

namespace Tests\Feature\Company;

use App\Enums\SupervisionPackageSku;
use App\Enums\SupervisorShiftStatus;
use App\Models\SupervisorShift;
use App\Models\SupervisorZone;
use App\Models\User;
use App\Services\Tenant\AssignCompanySupervisionPackageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CompanySupervisionMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_admin_can_open_supervision_map(): void
    {
        $this->seedWithPilot();

        $user = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();
        app(AssignCompanySupervisionPackageService::class)->execute(
            $user->securityCompany,
            SupervisionPackageSku::Sit1,
        );

        $response = $this->actingAs($user)->get(route('company.supervision.index'));

        $response->assertOk();
        $response->assertSee('Supervisión');
        $response->assertSee('En vivo');
        $response->assertSee('Historial / replay');
        $response->assertSee('Resumen');
        $response->assertSee('Descargar PPTX');
        $response->assertSee('Hoy');
        $response->assertSee('Mes');
        $response->assertSee('Año');
        $response->assertSee('Zona');
        $response->assertSee('Supervisor');
        $response->assertSee('Norte');
        $response->assertSee('Supervisor Zona Demo');
        $response->assertSee('Supervisores en turno');
        $response->assertDontSee('Nueve módulos');

        $summary = $this->actingAs($user)->get(route('company.supervision.index', ['tab' => 'summary']));
        $summary->assertOk();
        $summary->assertSee('Cobertura de sitios');
        $summary->assertSee('Revistas');
        $summary->assertDontSee('Nueve módulos');
    }

    public function test_supervision_map_filters_by_zone_and_supervisor(): void
    {
        $this->seedWithPilot();

        $admin = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();
        app(AssignCompanySupervisionPackageService::class)->execute(
            $admin->securityCompany,
            SupervisionPackageSku::Sit1,
        );

        $supervisor = User::query()->where('email', 'supervisor@sj-seguridad.test')->firstOrFail();
        $other = User::factory()->create([
            'name' => 'Otro Supervisor Filtro',
            'security_company_id' => $supervisor->security_company_id,
            'is_active' => true,
        ]);
        $other->syncRoles(['supervisor']);

        $norte = SupervisorZone::query()
            ->where('security_company_id', $supervisor->security_company_id)
            ->where('name', 'Norte')
            ->firstOrFail();
        $sur = SupervisorZone::query()
            ->where('security_company_id', $supervisor->security_company_id)
            ->where('name', 'Sur')
            ->firstOrFail();

        SupervisorShift::query()->create([
            'security_company_id' => $supervisor->security_company_id,
            'user_id' => $supervisor->id,
            'status' => SupervisorShiftStatus::Open,
            'supervisor_zone_id' => $norte->id,
            'started_at' => now(),
        ]);
        SupervisorShift::query()->create([
            'security_company_id' => $supervisor->security_company_id,
            'user_id' => $other->id,
            'status' => SupervisorShiftStatus::Open,
            'supervisor_zone_id' => $sur->id,
            'started_at' => now(),
        ]);

        $all = $this->actingAs($admin)->get(route('company.supervision.index'));
        $all->assertOk();
        $all->assertSee('"user":"Supervisor Zona Demo"', false);
        $all->assertSee('"user":"Otro Supervisor Filtro"', false);

        $filtered = $this->actingAs($admin)->get(route('company.supervision.index', [
            'zone_id' => $norte->id,
            'supervisor_id' => $supervisor->id,
        ]));
        $filtered->assertOk();
        $filtered->assertSee('"user":"Supervisor Zona Demo"', false);
        $filtered->assertDontSee('"user":"Otro Supervisor Filtro"', false);
    }

    public function test_company_admin_can_download_supervision_pptx(): void
    {
        $this->seedWithPilot();

        $user = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();

        $response = $this->actingAs($user)->get(route('company.supervision.report', [
            'from' => now()->startOfMonth()->toDateString(),
            'to' => now()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString(
            'Informe_Supervision_',
            (string) $response->headers->get('content-disposition'),
        );
        $this->assertStringContainsString(
            now()->startOfMonth()->toDateString(),
            (string) $response->headers->get('content-disposition'),
        );
    }
}
