<?php

declare(strict_types=1);

namespace Tests\Feature\Company;

use App\Models\Client;
use App\Models\User;
use App\Support\Company\CompanyOperateContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CompanyClientExpedienteTest extends TestCase
{
    use RefreshDatabase;

    public function test_clients_index_only_shows_ver_action(): void
    {
        $this->seedWithPilot();

        $user = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();

        $response = $this->actingAs($user)->get(route('company.clients.index'));

        $response->assertOk();
        $response->assertSee('Ver');
        $response->assertDontSee('>Operar</');
        $response->assertDontSee('>Editar</');
    }

    public function test_client_show_renders_expediente_and_operate_actions(): void
    {
        $this->seedWithPilot();

        $user = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();
        $client = Client::query()->where('slug', 'palmas-del-ingenio')->firstOrFail();

        $response = $this->actingAs($user)->get(route('company.clients.show', $client));

        $response->assertOk();
        $response->assertSee('Accesos');
        $response->assertSee('Cliente');
        $response->assertSee('Operar portería');
        $response->assertSee('Operar cliente');
        $response->assertSee('Editar');
        $response->assertSee('Personas (censo)');
        $response->assertSee('Usuarios app');
        $response->assertSee('Parque vehicular');
        $response->assertSee('Guardas asignados');
        $response->assertSee('← Cartera');
    }

    public function test_operate_client_opens_client_panel(): void
    {
        $this->seedWithPilot();

        $user = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();
        $client = Client::query()->where('slug', 'palmas-del-ingenio')->firstOrFail();

        $response = $this->actingAs($user)->post(route('company.clients.operate-client', $client));

        $response->assertRedirect(route('client.dashboard'));
        $this->assertEquals(
            $client->id,
            session(config('tenancy.session.active_client_key')),
        );
        $this->assertSame((int) $client->id, CompanyOperateContext::clientId());
        $this->assertSame(CompanyOperateContext::MODE_CLIENTE, CompanyOperateContext::mode());
    }

    public function test_operate_porteria_and_exit_returns_to_expediente(): void
    {
        $this->seedWithPilot();

        $user = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();
        $client = Client::query()->where('slug', 'palmas-del-ingenio')->firstOrFail();

        $enter = $this->actingAs($user)->post(route('company.clients.activate', $client));
        $enter->assertRedirect(route('access.dashboard'));
        $this->assertSame(CompanyOperateContext::MODE_PORTERIA, CompanyOperateContext::mode());

        $dashboard = $this->actingAs($user)
            ->withSession([
                config('tenancy.session.active_client_key') => $client->id,
                CompanyOperateContext::SESSION_CLIENT_KEY => $client->id,
                CompanyOperateContext::SESSION_MODE_KEY => CompanyOperateContext::MODE_PORTERIA,
            ])
            ->get(route('access.dashboard'));

        $dashboard->assertOk();
        $dashboard->assertSee('Volver al expediente');
        $dashboard->assertSee($client->name);

        $exit = $this->actingAs($user)
            ->withSession([
                CompanyOperateContext::SESSION_CLIENT_KEY => $client->id,
                CompanyOperateContext::SESSION_MODE_KEY => CompanyOperateContext::MODE_PORTERIA,
            ])
            ->post(route('company.operate.exit'));

        $exit->assertRedirect(route('company.clients.show', $client));
        $this->assertNull(CompanyOperateContext::clientId());
    }

    public function test_operate_client_and_exit_returns_to_expediente(): void
    {
        $this->seedWithPilot();

        $user = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();
        $client = Client::query()->where('slug', 'palmas-del-ingenio')->firstOrFail();

        $this->actingAs($user)->post(route('company.clients.operate-client', $client));

        $dashboard = $this->actingAs($user)
            ->withSession([
                config('tenancy.session.active_client_key') => $client->id,
                CompanyOperateContext::SESSION_CLIENT_KEY => $client->id,
                CompanyOperateContext::SESSION_MODE_KEY => CompanyOperateContext::MODE_CLIENTE,
            ])
            ->get(route('client.dashboard'));

        $dashboard->assertOk();
        $dashboard->assertSee('Volver al expediente');
        $dashboard->assertSee('panel del conjunto');

        $exit = $this->actingAs($user)
            ->withSession([
                CompanyOperateContext::SESSION_CLIENT_KEY => $client->id,
                CompanyOperateContext::SESSION_MODE_KEY => CompanyOperateContext::MODE_CLIENTE,
            ])
            ->post(route('company.operate.exit'));

        $exit->assertRedirect(route('company.clients.show', $client));
        $this->assertNull(CompanyOperateContext::clientId());
    }
}
