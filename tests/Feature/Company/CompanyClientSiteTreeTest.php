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
        ])->assertRedirect(route('company.clients.show', [$client, 'vista' => 'accesos']));

        $installation = Installation::query()
            ->where('client_id', $client->id)
            ->where('name', 'Bodega norte')
            ->firstOrFail();

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
}
