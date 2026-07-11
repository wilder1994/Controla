<?php

declare(strict_types=1);

namespace Tests\Feature\Access;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AccessAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guard_without_assigned_clients_cannot_access_porteria(): void
    {
        $this->seed();

        $guard = User::factory()->create([
            'email' => 'guardia-sin-cliente@test.test',
            'is_active' => true,
        ]);
        $guard->syncRoles(['guardia']);

        $response = $this->actingAs($guard)->get(route('access.dashboard'));

        $response->assertForbidden();
    }

    public function test_company_admin_without_visitor_permission_cannot_manage_visitors(): void
    {
        $this->seed();

        $client = Client::query()->where('slug', 'palmas-del-ingenio')->first();
        $admin = User::query()->where('email', 'empresa@sj-seguridad.test')->first();

        $this->assertNotNull($client);
        $this->assertNotNull($admin);

        $response = $this->actingAs($admin)
            ->withSession(['tenancy.active_client_id' => $client->id])
            ->get(route('access.visitors.index'));

        $response->assertForbidden();
    }

    public function test_guard_with_client_session_can_access_dashboard(): void
    {
        $this->seed();

        $client = Client::query()->where('slug', 'palmas-del-ingenio')->first();
        $guard = User::query()->where('email', 'guardia@control-acceso.test')->first();

        $this->assertNotNull($client);
        $this->assertNotNull($guard);

        $response = $this->actingAs($guard)
            ->withSession(['tenancy.active_client_id' => $client->id])
            ->get(route('access.dashboard'));

        $response->assertOk();
    }

    public function test_guard_with_multiple_clients_redirects_to_client_selection(): void
    {
        $this->seed();

        $palmas = Client::query()->where('slug', 'palmas-del-ingenio')->first();
        $torres = Client::query()->where('slug', 'torres-loma')->first();
        $guard = User::query()->where('email', 'guardia@control-acceso.test')->first();

        $this->assertNotNull($palmas);
        $this->assertNotNull($torres);
        $this->assertNotNull($guard);

        $guard->clients()->syncWithoutDetaching([
            $palmas->id => ['is_primary' => true, 'assigned_at' => now()],
            $torres->id => ['is_primary' => false, 'assigned_at' => now()],
        ]);

        $response = $this->actingAs($guard)
            ->get(route('access.dashboard'));

        $response->assertRedirect(route('company.clients.select'));
    }
}
