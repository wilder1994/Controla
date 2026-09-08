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
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class CompanySupervisionMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_admin_can_open_supervision_map(): void
    {
        $this->seedWithPilot();

        $user = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();
        $supervisor = $this->companySupervisor();
        app(AssignCompanySupervisionPackageService::class)->execute(
            $user->securityCompany,
            SupervisionPackageSku::Sit1,
        );

        $response = $this->actingAs($user)->get(route('company.supervision.index'));

        $response->assertOk();
        $response->assertSee('Supervisión');
        $response->assertSee('En vivo');
        $response->assertSee('Historial');
        $response->assertSee('Resumen');
        $response->assertSee('Fichas');
        $response->assertSee('Descargar PPTX');
        $response->assertSee('Hoy');
        $response->assertSee('Mes');
        $response->assertSee('Año');
        $response->assertSee('Zona');
        $response->assertSee('Supervisor');
        $response->assertSee('Norte');
        $response->assertSee($supervisor->name);
        $response->assertSee('Supervisores en turno');
        $response->assertSee('Se actualiza solo');
        $response->assertSee('Satélite');
        $response->assertSee('Terreno');
        $response->assertSee('Palmas');
        $response->assertDontSee('Nueve módulos');

        $history = $this->actingAs($user)->get(route('company.supervision.index', ['tab' => 'history']));
        $history->assertOk();
        $history->assertSee('Turnos del periodo');
        $history->assertSee('Una ruta a la vez');
        $history->assertDontSee('Replay');
        $history->assertDontSee('Reproducir');

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

        $supervisor = $this->companySupervisor();
        $other = $this->companySupervisor('Otro', 'Filtro', '1199005500', 'otro.filtro.5500');

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
        $all->assertSee('"user":"'.$supervisor->name.'"', false);
        $all->assertSee('"user":"'.$other->name.'"', false);

        $filtered = $this->actingAs($admin)->get(route('company.supervision.index', [
            'zone_id' => $norte->id,
            'supervisor_id' => $supervisor->id,
        ]));
        $filtered->assertOk();
        $filtered->assertSee('"user":"'.$supervisor->name.'"', false);
        $filtered->assertDontSee('"user":"'.$other->name.'"', false);
    }

    public function test_live_map_embeds_client_pins_and_shift_path(): void
    {
        $this->seedWithPilot();

        $admin = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();
        app(AssignCompanySupervisionPackageService::class)->execute(
            $admin->securityCompany,
            SupervisionPackageSku::Sit1,
        );

        $supervisor = $this->companySupervisor();
        $shift = SupervisorShift::query()->create([
            'security_company_id' => $supervisor->security_company_id,
            'user_id' => $supervisor->id,
            'status' => SupervisorShiftStatus::Open,
            'started_at' => now()->subHour(),
        ]);
        $shift->locations()->create([
            'recorded_at' => now()->subMinutes(4),
            'latitude' => 3.4516,
            'longitude' => -76.5320,
            'source' => 'gps',
        ]);
        $shift->locations()->create([
            'recorded_at' => now()->subMinute(),
            'latitude' => 3.4530,
            'longitude' => -76.5340,
            'source' => 'gps',
        ]);

        $response = $this->actingAs($admin)->get(route('company.supervision.index'));
        $response->assertOk();
        $response->assertSee('"name":"Palmas del Ingenio"', false);
        $response->assertSee('"path":', false);
        $response->assertSee('en ruta');
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

    public function test_live_feed_json_lists_open_shifts(): void
    {
        $this->seedWithPilot();

        $admin = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();
        app(AssignCompanySupervisionPackageService::class)->execute(
            $admin->securityCompany,
            SupervisionPackageSku::Sit1,
        );
        $supervisor = $this->companySupervisor();
        SupervisorShift::query()->create([
            'security_company_id' => $supervisor->security_company_id,
            'user_id' => $supervisor->id,
            'status' => SupervisorShiftStatus::Open,
            'started_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($admin)->getJson(route('company.supervision.live-feed'));
        $response->assertOk();
        $response->assertJsonPath('live.0.user', $supervisor->name);
        $response->assertJsonStructure(['live', 'reviews']);
    }

    public function test_snapped_route_uses_roads_once_for_closed_shift(): void
    {
        $this->seedWithPilot();

        $admin = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();
        app(AssignCompanySupervisionPackageService::class)->execute(
            $admin->securityCompany,
            SupervisionPackageSku::Sit1,
        );
        $supervisor = $this->companySupervisor();
        $shift = SupervisorShift::query()->create([
            'security_company_id' => $supervisor->security_company_id,
            'user_id' => $supervisor->id,
            'status' => SupervisorShiftStatus::Closed,
            'started_at' => now()->subHours(2),
            'ended_at' => now(),
        ]);
        $shift->locations()->create([
            'recorded_at' => now()->subMinutes(20),
            'latitude' => 3.4516,
            'longitude' => -76.5320,
            'source' => 'gps',
        ]);
        $shift->locations()->create([
            'recorded_at' => now()->subMinutes(10),
            'latitude' => 3.4600,
            'longitude' => -76.5400,
            'source' => 'gps',
        ]);

        Http::fake([
            'roads.googleapis.com/*' => Http::response([
                'snappedPoints' => [
                    ['location' => ['latitude' => 3.4517, 'longitude' => -76.5321]],
                    ['location' => ['latitude' => 3.4601, 'longitude' => -76.5401]],
                ],
            ], 200),
        ]);
        config(['google-maps.server_api_key' => 'test-roads-key']);

        $first = $this->actingAs($admin)->getJson(route('company.supervision.snapped-route', $shift));
        $first->assertOk();
        $first->assertJsonPath('snapped', true);
        $first->assertJsonPath('path.0.lat', 3.4517);

        $second = $this->actingAs($admin)->getJson(route('company.supervision.snapped-route', $shift));
        $second->assertOk();
        $second->assertJsonPath('snapped', true);
        Http::assertSentCount(1);
    }
}
