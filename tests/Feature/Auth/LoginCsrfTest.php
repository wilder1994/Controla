<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LoginCsrfTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_login_redirects_to_platform_dashboard(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'admin@control-acceso.test')->first();
        $this->assertNotNull($user);

        $response = $this->get(route('login'));
        $response->assertOk();

        $response = $this->post(route('login'), [
            'email' => 'admin@control-acceso.test',
            'password' => 'Admin123!',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_super_admin_can_access_platform_dashboard(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'admin@control-acceso.test')->first();

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Panel de plataforma');
    }

    public function test_super_admin_can_operate_porteria_via_client_select(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'admin@control-acceso.test')->first();
        $client = \App\Models\Client::query()->where('slug', 'palmas-del-ingenio')->first();

        $response = $this->actingAs($user)->get(route('company.clients.select'));
        $response->assertOk();
        $response->assertSee('Palmas del Ingenio');

        $response = $this->actingAs($user)->post(route('company.clients.activate', $client));
        $response->assertRedirect(route('access.dashboard'));
    }

    public function test_legacy_dashboard_url_redirects_to_home(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'admin@control-acceso.test')->first();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('home'));
    }
}
