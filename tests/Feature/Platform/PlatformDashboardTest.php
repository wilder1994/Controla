<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PlatformDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_sees_platform_metrics(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@control-acceso.test')->first();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Empresas');
        $response->assertSee('SJ Seguridad');
    }

    public function test_guard_cannot_access_platform_dashboard(): void
    {
        $this->seed();

        $guard = User::query()->where('email', 'guardia@control-acceso.test')->first();

        $response = $this->actingAs($guard)->get(route('admin.dashboard'));

        $response->assertForbidden();
    }
}
