<?php

declare(strict_types=1);

namespace Tests\Feature\Client;

use App\Models\Client;
use App\Models\MemberType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ClientMemberTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_admin_can_manage_person_types(): void
    {
        $this->seedWithPilot();

        $client = Client::query()->where('slug', 'palmas-del-ingenio')->firstOrFail();
        $admin = User::query()->where('email', 'admin@palmasdelingenio.test')->firstOrFail();

        $this->actingAs($admin)
            ->withSession(['tenancy.active_client_id' => $client->id])
            ->get(route('client.settings.member-types.index'))
            ->assertOk()
            ->assertSee('Tipos de persona')
            ->assertSee('Propietario');

        $this->actingAs($admin)
            ->withSession(['tenancy.active_client_id' => $client->id])
            ->post(route('client.settings.member-types.store'), [
                'name' => 'Contratista',
                'is_active' => true,
            ])
            ->assertRedirect(route('client.settings.member-types.index'));

        $type = MemberType::query()
            ->where('client_id', $client->id)
            ->where('name', 'Contratista')
            ->firstOrFail();

        $this->assertSame('contratista', $type->slug);

        $this->actingAs($admin)
            ->withSession(['tenancy.active_client_id' => $client->id])
            ->put(route('client.settings.member-types.update', $type), [
                'name' => 'Contratista externo',
                'is_active' => false,
            ])
            ->assertRedirect(route('client.settings.member-types.index'));

        $this->assertDatabaseHas('member_types', [
            'id' => $type->id,
            'name' => 'Contratista externo',
            'is_active' => 0,
        ]);
    }

    public function test_cannot_delete_type_in_use(): void
    {
        $this->seedWithPilot();

        $client = Client::query()->where('slug', 'palmas-del-ingenio')->firstOrFail();
        $admin = User::query()->where('email', 'admin@palmasdelingenio.test')->firstOrFail();
        $type = MemberType::query()
            ->where('client_id', $client->id)
            ->where('name', 'Propietario')
            ->firstOrFail();

        $this->actingAs($admin)
            ->withSession(['tenancy.active_client_id' => $client->id])
            ->from(route('client.settings.member-types.index'))
            ->delete(route('client.settings.member-types.destroy', $type))
            ->assertRedirect(route('client.settings.member-types.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('member_types', ['id' => $type->id]);
    }
}
