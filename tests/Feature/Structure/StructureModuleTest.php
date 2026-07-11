<?php

declare(strict_types=1);

namespace Tests\Feature\Structure;

use App\Enums\MemberType;
use App\Enums\StructureType;
use App\Models\Client;
use App\Models\Structure;
use App\Models\StructureMember;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StructureModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_admin_can_create_structure_with_tenant_scope(): void
    {
        $this->seed();

        $client = Client::query()->where('slug', 'palmas-del-ingenio')->first();
        $admin = User::query()->where('email', 'admin@palmasdelingenio.test')->first();

        $this->assertNotNull($client);
        $this->assertNotNull($admin);

        $response = $this->actingAs($admin)
            ->withSession(['tenancy.active_client_id' => $client->id])
            ->post(route('client.structures.store'), [
                'name' => 'Torre Piloto Test',
                'code' => 'TORRE-TEST',
                'type' => StructureType::Block->value,
                'is_active' => true,
            ]);

        $response->assertRedirect(route('client.structures.index'));

        $structure = Structure::withoutGlobalScopes()
            ->where('code', 'TORRE-TEST')
            ->first();

        $this->assertNotNull($structure);
        $this->assertSame($client->id, $structure->client_id);
    }

    public function test_member_and_vehicle_are_isolated_by_client(): void
    {
        $this->seed();

        $clientA = Client::query()->where('slug', 'palmas-del-ingenio')->first();
        $clientB = Client::query()->where('slug', 'torres-loma')->first();
        $admin = User::query()->where('email', 'admin@palmasdelingenio.test')->first();

        $structureB = Structure::withoutGlobalScopes()->create([
            'client_id' => $clientB->id,
            'name' => 'Apto B1',
            'code' => 'B1-TEST',
            'type' => StructureType::Apartment,
            'is_active' => true,
        ]);

        StructureMember::withoutGlobalScopes()->create([
            'client_id' => $clientB->id,
            'structure_id' => $structureB->id,
            'first_name' => 'Otro',
            'last_name' => 'Cliente',
            'document_number' => '999999999',
            'member_type' => MemberType::Owner,
            'access_code' => 'SECRETB999',
            'is_active' => true,
        ]);

        app(TenantContext::class)->setClientId($clientA->id);

        $visibleDocs = StructureMember::query()->pluck('document_number')->all();

        $this->assertNotContains('999999999', $visibleDocs);
    }

    public function test_client_admin_can_access_structures_index(): void
    {
        $this->seed();

        $client = Client::query()->where('slug', 'palmas-del-ingenio')->first();
        $admin = User::query()->where('email', 'admin@palmasdelingenio.test')->first();

        $response = $this->actingAs($admin)
            ->withSession(['tenancy.active_client_id' => $client->id])
            ->get(route('client.structures.index'));

        $response->assertOk();
        $response->assertSee('Estructura residencial');
    }

    public function test_pilot_seed_creates_tower_and_members(): void
    {
        $this->seed();

        $client = Client::query()->where('slug', 'palmas-del-ingenio')->first();

        $structures = Structure::withoutGlobalScopes()->where('client_id', $client->id)->count();
        $members = StructureMember::withoutGlobalScopes()->where('client_id', $client->id)->count();

        $this->assertGreaterThanOrEqual(12, $structures);
        $this->assertGreaterThanOrEqual(20, $members);
    }

    public function test_client_admin_can_create_member_via_wizard(): void
    {
        $this->seed();

        $client = Client::query()->where('slug', 'palmas-del-ingenio')->first();
        $admin = User::query()->where('email', 'admin@palmasdelingenio.test')->first();
        $structure = Structure::withoutGlobalScopes()
            ->where('client_id', $client->id)
            ->where('type', StructureType::Apartment)
            ->first();

        $this->assertNotNull($structure);

        $this->actingAs($admin)
            ->withSession(['tenancy.active_client_id' => $client->id])
            ->post(route('client.members.create.step1'), [
                'first_name' => 'Nueva',
                'last_name' => 'Persona',
                'document_number' => '5551234567',
                'structure_id' => $structure->id,
                'member_type' => MemberType::Owner->value,
                'phone_primary' => '+573001112233',
            ])
            ->assertRedirect(route('client.members.create.confirm'));

        $response = $this->actingAs($admin)
            ->withSession(['tenancy.active_client_id' => $client->id])
            ->post(route('client.members.store'), [
                'has_app_access' => true,
                'is_active' => true,
            ]);

        $response->assertRedirect();

        $member = StructureMember::withoutGlobalScopes()
            ->where('document_number', '5551234567')
            ->first();

        $this->assertNotNull($member);
        $this->assertSame($client->id, $member->client_id);
        $this->assertNotEmpty($member->access_code);
    }

    public function test_client_admin_can_create_and_update_vehicle_with_tenant_scope(): void
    {
        $this->seed();

        $client = Client::query()->where('slug', 'palmas-del-ingenio')->first();
        $admin = User::query()->where('email', 'admin@palmasdelingenio.test')->first();
        $structure = Structure::withoutGlobalScopes()
            ->where('client_id', $client->id)
            ->where('type', StructureType::Apartment)
            ->first();

        $this->assertNotNull($structure);

        $createResponse = $this->actingAs($admin)
            ->withSession(['tenancy.active_client_id' => $client->id])
            ->post(route('client.vehicles.store'), [
                'structure_id' => $structure->id,
                'plate' => 'XYZ999',
                'brand' => 'Mazda',
                'model' => '3',
                'color' => 'Rojo',
                'is_visitor_vehicle' => false,
            ]);

        $createResponse->assertRedirect(route('client.vehicles.index'));

        $vehicle = Vehicle::withoutGlobalScopes()->where('plate', 'XYZ999')->first();
        $this->assertNotNull($vehicle);
        $this->assertSame($client->id, $vehicle->client_id);

        $updateResponse = $this->actingAs($admin)
            ->withSession(['tenancy.active_client_id' => $client->id])
            ->patch(route('client.vehicles.update', $vehicle), [
                'structure_id' => $structure->id,
                'plate' => 'XYZ999',
                'brand' => 'Mazda',
                'model' => 'CX-5',
                'color' => 'Azul',
                'is_visitor_vehicle' => false,
            ]);

        $updateResponse->assertRedirect(route('client.vehicles.index'));
        $this->assertSame('CX-5', $vehicle->fresh()->model);
    }
}
