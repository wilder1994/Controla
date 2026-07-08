<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Models\Client;
use App\Models\Location;
use App\Models\SecurityCompany;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guard_only_sees_locations_for_active_client(): void
    {
        $this->seed();

        $company = SecurityCompany::query()->first();
        $this->assertNotNull($company);

        $clientA = Client::query()->where('slug', 'palmas-del-ingenio')->first();
        $clientB = Client::query()->where('slug', 'torres-loma')->first();

        Location::query()->create([
            'client_id' => $clientB->id,
            'code' => 'PORT-B',
            'name' => 'Portería Torres',
            'type' => 'porteria',
            'is_active' => true,
        ]);

        $guard = User::query()->where('email', 'guardia@control-acceso.test')->first();
        $this->assertNotNull($guard);

        $context = app(TenantContext::class);
        $context->setClientId($clientA->id);

        $visibleCodes = Location::query()->pluck('code')->all();

        $this->assertContains('PORT-01', $visibleCodes);
        $this->assertNotContains('PORT-B', $visibleCodes);
    }

    public function test_company_admin_can_access_company_panel(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'empresa@sj-seguridad.test')->first();
        $this->assertNotNull($admin);

        $response = $this->actingAs($admin)->get(route('company.dashboard'));

        $response->assertOk();
        $response->assertSee('Vista consolidada');
    }
}
