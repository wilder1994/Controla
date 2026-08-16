<?php

declare(strict_types=1);

namespace Tests\Feature\Company;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CompanyClientExpedienteTest extends TestCase
{
    use RefreshDatabase;

    public function test_clients_index_only_shows_ver_action(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();

        $response = $this->actingAs($user)->get(route('company.clients.index'));

        $response->assertOk();
        $response->assertSee('Ver');
        $response->assertDontSee('>Operar</');
        $response->assertDontSee('>Editar</');
    }

    public function test_client_show_renders_expediente_and_operate_actions(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();
        $client = Client::query()->where('slug', 'palmas-del-ingenio')->firstOrFail();

        $response = $this->actingAs($user)->get(route('company.clients.show', $client));

        $response->assertOk();
        $response->assertSee('Resumen');
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
        $this->seed();

        $user = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();
        $client = Client::query()->where('slug', 'palmas-del-ingenio')->firstOrFail();

        $response = $this->actingAs($user)->post(route('company.clients.operate-client', $client));

        $response->assertRedirect(route('client.dashboard'));
        $this->assertEquals(
            $client->id,
            session(config('tenancy.session.active_client_key')),
        );
    }
}
