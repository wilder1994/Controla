<?php

declare(strict_types=1);

namespace Tests\Feature\Access;

use App\Models\AccessLog;
use App\Models\Client;
use App\Models\Location;
use App\Models\StructureMember;
use App\Models\User;
use App\Models\Visitor;
use App\Services\Access\PorteriaDoorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PorteriaConsoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_and_directory_use_census_people_not_employees(): void
    {
        [$vigilante, $client] = $this->vigilante();
        $session = $this->porteriaSession($client);

        $this->actingAs($vigilante)->withSession($session)
            ->get(route('access.dashboard'))
            ->assertOk()
            ->assertSee('Personas dentro')
            ->assertSee('Ingreso y salida');

        $member = StructureMember::query()->where('client_id', $client->id)->first();
        $this->assertNotNull($member);

        $this->actingAs($vigilante)->withSession($session)
            ->get(route('access.people.index'))
            ->assertOk()
            ->assertSee($member->first_name)
            ->assertDontSee('Directorio de empleados');

        $this->actingAs($vigilante)->withSession($session)
            ->get(route('access.visitors.index', ['tab' => 'vehiculos']))
            ->assertOk()
            ->assertSee('Vehículos');
    }

    public function test_lookup_and_visitor_entry_at_operating_door(): void
    {
        [$vigilante, $client] = $this->vigilante();
        $door = Location::query()->withoutGlobalScopes()->where('client_id', $client->id)->where('is_active', true)->firstOrFail();
        $session = $this->porteriaSession($client, (int) $door->id);

        $this->actingAs($vigilante)->withSession($session)
            ->post(route('access.turnos.store'), ['location_id' => $door->id])
            ->assertRedirect();

        $session[PorteriaDoorService::SESSION_KEY] = $door->id;

        $this->actingAs($vigilante)->withSession($session)
            ->post(route('access.logs.entry.store'), [
                'subject_kind' => 'visitor',
                'first_name' => 'Ana',
                'last_name' => 'Visitante',
                'document_type' => 'CC',
                'document_number' => '1099001122',
                'purpose' => 'Entrega',
            ])
            ->assertRedirect(route('access.logs.index'));

        $this->assertDatabaseHas('visitors', [
            'client_id' => $client->id,
            'document_number' => '1099001122',
        ]);
        $visitor = Visitor::query()->where('document_number', '1099001122')->firstOrFail();
        $this->assertDatabaseHas('access_logs', [
            'visitor_id' => $visitor->id,
            'location_id' => $door->id,
            'status' => 'active',
            'access_type' => 'visitor',
        ]);

        $this->actingAs($vigilante)->withSession($session)
            ->getJson(route('access.logs.lookup', ['q' => '1099001122']))
            ->assertOk()
            ->assertJsonPath('visitors.0.name', 'Ana Visitante');

        $log = AccessLog::query()->where('visitor_id', $visitor->id)->firstOrFail();
        $this->actingAs($vigilante)->withSession($session)
            ->patch(route('access.logs.exit', $log))
            ->assertRedirect();
        $this->assertSame('completed', $log->fresh()->status);
    }

    /** @return array{0: User, 1: Client} */
    private function vigilante(): array
    {
        $this->seedWithPilot();
        config(['access.geo.required' => false, 'access.shifts.enforced' => true]);

        return [
            User::query()->where('email', 'guardia@control-acceso.test')->firstOrFail(),
            Client::query()->where('slug', 'palmas-del-ingenio')->firstOrFail(),
        ];
    }
}
