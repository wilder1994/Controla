<?php

declare(strict_types=1);

namespace Tests\Feature\Company;

use App\Models\Client;
use App\Models\Installation;
use App\Models\Location;
use App\Models\SupervisorPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CompanyClientSiteTreeTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_creates_installation_access_and_post(): void
    {
        $this->seedWithPilot();

        $user = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();
        $client = Client::query()->where('slug', 'palmas-del-ingenio')->firstOrFail();

        $this->actingAs($user)->post(route('company.clients.installations.store', $client), [
            'name' => 'Bodega norte',
            'vista' => 'accesos',
            'address' => 'Calle 9 # 10-11',
            'city' => 'Cali',
            'department' => 'Valle del Cauca',
            'latitude' => '3.4516000',
            'longitude' => '-76.5320000',
        ])->assertRedirect(route('company.clients.show', [$client, 'vista' => 'accesos']));

        $installation = Installation::query()
            ->where('client_id', $client->id)
            ->where('name', 'Bodega norte')
            ->firstOrFail();
        $this->assertSame('Cali', $installation->city);
        $this->assertNotNull($installation->latitude);

        $this->actingAs($user)->post(route('company.clients.locations.store', $client), [
            'installation_id' => $installation->id,
            'code' => 'BN-01',
            'name' => 'Vehicular norte',
        ])->assertRedirect(route('company.clients.show', [$client, 'vista' => 'accesos']));

        $this->assertTrue(
            Location::query()
                ->where('client_id', $client->id)
                ->where('code', 'BN-01')
                ->where('installation_id', $installation->id)
                ->exists()
        );

        $this->actingAs($user)->post(route('company.clients.posts.store', $client), [
            'installation_id' => $installation->id,
            'name' => 'Puesto bodega',
        ])->assertRedirect(route('company.clients.show', [$client, 'vista' => 'supervision']));

        $this->assertTrue(
            SupervisorPost::query()
                ->where('client_id', $client->id)
                ->where('name', 'Puesto bodega')
                ->exists()
        );
    }

    public function test_access_only_client_cannot_create_supervision_post(): void
    {
        $this->seedWithPilot();

        $user = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();
        $client = Client::query()->where('slug', 'torres-loma')->firstOrFail();
        $installation = Installation::query()->where('client_id', $client->id)->firstOrFail();

        $this->actingAs($user)
            ->from(route('company.clients.show', [$client, 'vista' => 'accesos']))
            ->post(route('company.clients.posts.store', $client), [
                'installation_id' => $installation->id,
                'name' => 'Puesto inválido',
            ])
            ->assertForbidden();
    }

    public function test_same_client_installation_copies_name_and_geo(): void
    {
        $this->seedWithPilot();

        $user = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();
        $client = Client::query()->create([
            'security_company_id' => $user->security_company_id,
            'name' => 'SOS Occidente',
            'party_type' => 'legal_entity',
            'legal_name' => 'SOS Occidente S.A.S.',
            'document_type' => 'NIT',
            'tax_id' => '901777888-9',
            'email' => 'sos@test.com',
            'structure_type_id' => \App\Models\StructureType::idByCode((int) $user->security_company_id, 'ph'),
            'slug' => 'sos-occidente',
            'login_suffix' => 'sosoccidente',
            'address' => 'Av 5 # 1-20',
            'city' => 'Cali',
            'department' => 'Valle del Cauca',
            'latitude' => 3.4401,
            'longitude' => -76.5222,
            'is_active' => true,
            'has_access' => true,
            'has_supervision' => true,
        ]);

        $this->actingAs($user)->post(route('company.clients.installations.store', $client), [
            'name' => 'otro nombre',
            'is_client_site' => '1',
            'vista' => 'accesos',
        ])->assertRedirect(route('company.clients.show', [$client, 'vista' => 'accesos']));

        $installation = Installation::query()->where('client_id', $client->id)->firstOrFail();
        $this->assertSame('SOS Occidente', $installation->name);
        $this->assertTrue($installation->is_client_site);
        $this->assertSame('Av 5 # 1-20', $installation->address);
        $this->assertSame('Cali', $installation->city);
        $this->assertEqualsWithDelta(3.4401, (float) $installation->latitude, 0.0001);
    }

    public function test_installation_without_map_is_rejected(): void
    {
        $this->seedWithPilot();

        $user = User::query()->where('email', 'empresa@sj-seguridad.test')->firstOrFail();
        $client = Client::query()->where('slug', 'palmas-del-ingenio')->firstOrFail();

        $this->actingAs($user)
            ->from(route('company.clients.show', [$client, 'vista' => 'accesos']))
            ->post(route('company.clients.installations.store', $client), [
                'name' => 'Sin pin',
                'vista' => 'accesos',
            ])
            ->assertRedirect(route('company.clients.show', [$client, 'vista' => 'accesos']))
            ->assertSessionHasErrors('latitude');
    }
}
