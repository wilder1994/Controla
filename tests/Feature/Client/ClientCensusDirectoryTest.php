<?php

declare(strict_types=1);

namespace Tests\Feature\Client;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ClientCensusDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_members_index_uses_installation_and_node_copy(): void
    {
        $this->seedWithPilot();

        $client = Client::query()->where('slug', 'palmas-del-ingenio')->firstOrFail();
        $admin = User::query()->where('email', 'admin@palmasdelingenio.test')->firstOrFail();

        $this->actingAs($admin)
            ->withSession(['tenancy.active_client_id' => $client->id])
            ->get(route('client.members.index'))
            ->assertOk()
            ->assertSee('Personas')
            ->assertSee('Instalación')
            ->assertSee('Código de acceso')
            ->assertSee('Nueva persona')
            ->assertSee('Exportar')
            ->assertDontSee('Directorio de personas')
            ->assertDontSee('Todas las unidades');
    }

    public function test_vehicles_and_pets_index_drop_unidad_copy(): void
    {
        $this->seedWithPilot();

        $client = Client::query()->where('slug', 'palmas-del-ingenio')->firstOrFail();
        $admin = User::query()->where('email', 'admin@palmasdelingenio.test')->firstOrFail();
        $session = ['tenancy.active_client_id' => $client->id];

        $this->actingAs($admin)
            ->withSession($session)
            ->get(route('client.vehicles.index'))
            ->assertOk()
            ->assertSee('Instalación')
            ->assertDontSee('Directorio vehicular')
            ->assertDontSee('Todas las unidades');

        $this->actingAs($admin)
            ->withSession($session)
            ->get(route('client.pets.index'))
            ->assertOk()
            ->assertSee('Instalación')
            ->assertDontSee('Registro de mascotas por unidad')
            ->assertDontSee('Todas las unidades');
    }
}
