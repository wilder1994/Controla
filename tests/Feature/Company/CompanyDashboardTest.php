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
        $this->seed();

        $admin = User::query()->where('email', 'empresa@sj-seguridad.test')->first();
        $this->assertNotNull($admin);

        $response = $this->actingAs($admin)->get(route('company.dashboard'));

        $response->assertOk();
        $response->assertSee('Resumen empresa');
        $response->assertSee('Cartera y operación');
        $response->assertSee('Vigilantes en turno ahora');
        $response->assertSee('Puestos con turno abierto');
        $response->assertSee('Vehículos · entradas hoy');
        $response->assertSee('Visitantes peatonales · entradas hoy');
        $response->assertSee('Pánicos abiertos sin cerrar');
        $response->assertSee('Bloqueos · vehículos activos');
        $response->assertSee('Bloqueos · personas activas');
        $response->assertSee('Conjuntos archivados');
        $response->assertSee('Revista (KPIs)');
        $response->assertSee('Cumplimiento revistas (mes)');
        $response->assertSee('Sin revista en turno');
        $response->assertSee('Mapa de conjuntos');
        $response->assertSee('Atención ahora');
        $response->assertSee('Fuerza laboral');
        $response->assertSee('Cumplimiento por conjunto');
        $response->assertSee('Revistas: realizadas vs esperadas');
        $response->assertSee('Accesos por conjunto (hoy)');
        $response->assertSee('Turnos abiertos ahora');
        $response->assertDontSee('Próximamente');
    }

    public function test_guard_cannot_access_company_dashboard(): void
    {
        $this->seed();

        $guard = User::query()->where('email', 'guardia@control-acceso.test')->first();
        $this->assertNotNull($guard);

        $response = $this->actingAs($guard)->get(route('company.dashboard'));

        $response->assertForbidden();
    }
}
