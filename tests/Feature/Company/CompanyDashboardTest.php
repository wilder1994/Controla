<?php

declare(strict_types=1);

namespace Tests\Feature\Company;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CompanyDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_admin_sees_command_center_kpis(): void
    {
        $this->seedWithPilot();

        $admin = User::query()->where('email', 'empresa@sj-seguridad.test')->first();
        $this->assertNotNull($admin);

        $response = $this->actingAs($admin)->get(route('company.dashboard'));

        $response->assertOk();
        $response->assertSee('Resumen empresa');
        $response->assertSee('Mapa de conjuntos');
        $response->assertSee('Cartera de clientes');
        $response->assertSee('Plan activo');
        $response->assertSee('Activos');
        $response->assertSee('Archivados');
        $response->assertSee('Disponibles');
        $response->assertSee('Resumen de alertas y registros (hoy)');
        $response->assertSee('Novedades');
        $response->assertSee('Correspondencia');
        $response->assertSee('Pánico');
        $response->assertSee('Bloqueos');
        $response->assertSee('Fuerza laboral actual');
        $response->assertSee('Accesos por conjunto (hoy)');
        $response->assertSee('Turnos abiertos');
        $response->assertSee('Revistas mensuales');
        $response->assertSee('Revistas de supervisión (7 días)');
        $response->assertDontSee('Cumplimiento por conjunto');
        $response->assertDontSee('Próximamente');
    }

    public function test_guard_cannot_access_company_dashboard(): void
    {
        $this->seedWithPilot();

        $guard = User::query()->where('email', 'guardia@control-acceso.test')->first();
        $this->assertNotNull($guard);

        $response = $this->actingAs($guard)->get(route('company.dashboard'));

        $response->assertForbidden();
    }
}
