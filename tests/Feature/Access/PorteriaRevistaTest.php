<?php

declare(strict_types=1);

namespace Tests\Feature\Access;

use App\Models\Client;
use App\Models\GuardLog;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PorteriaRevistaTest extends TestCase
{
    use RefreshDatabase;

    public function test_minuta_revista_accepts_company_supervisor_code(): void
    {
        $this->seedWithPilot();
        config(['access.geo.required' => false, 'access.shifts.enforced' => false]);

        $client = Client::query()->where('slug', 'palmas-del-ingenio')->firstOrFail();
        $vigilante = User::query()->where('email', 'guardia@control-acceso.test')->firstOrFail();
        $supervisor = $this->companySupervisor();
        $door = Location::query()
            ->withoutGlobalScopes()
            ->where('client_id', $client->id)
            ->where('is_active', true)
            ->firstOrFail();

        $response = $this->actingAs($vigilante)
            ->withSession($this->porteriaSession($client))
            ->post(route('access.guard_logs.store'), [
                'location_id' => $door->id,
                'log_time' => now()->format('Y-m-d H:i:s'),
                'type' => 'revista',
                'shift_type' => 'diurno',
                'description' => 'Revista de puesto en portería.',
                'signed' => '1',
                'supervision_code' => $supervisor->supervisor_code,
                'latitude' => 3.4516,
                'longitude' => -76.5320,
            ]);

        $response->assertRedirect(route('access.guard_logs.index'));
        $this->assertDatabaseHas('guard_logs', [
            'type' => 'revista',
            'supervisor_name' => $supervisor->name,
            'location_id' => $door->id,
        ]);
        $this->assertNotNull(GuardLog::query()->where('type', 'revista')->first());
    }

    public function test_minuta_revista_rejects_unknown_code(): void
    {
        $this->seedWithPilot();
        config(['access.geo.required' => false, 'access.shifts.enforced' => false]);

        $client = Client::query()->where('slug', 'palmas-del-ingenio')->firstOrFail();
        $vigilante = User::query()->where('email', 'guardia@control-acceso.test')->firstOrFail();
        $door = Location::query()
            ->withoutGlobalScopes()
            ->where('client_id', $client->id)
            ->where('is_active', true)
            ->firstOrFail();

        $response = $this->actingAs($vigilante)
            ->withSession($this->porteriaSession($client))
            ->from(route('access.guard_logs.create'))
            ->post(route('access.guard_logs.store'), [
                'location_id' => $door->id,
                'log_time' => now()->format('Y-m-d H:i:s'),
                'type' => 'revista',
                'shift_type' => 'diurno',
                'description' => 'Revista inválida.',
                'signed' => '1',
                'supervision_code' => '000000',
                'latitude' => 3.4516,
                'longitude' => -76.5320,
            ]);

        $response->assertSessionHasErrors('supervision_code');
    }
}
