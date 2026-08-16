<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Enums\MemberType;
use App\Enums\StructureType;
use App\Models\AccessLog;
use App\Models\Blocklist;
use App\Models\Client;
use App\Models\Location;
use App\Models\Structure;
use App\Models\StructureAppUser;
use App\Models\StructureMember;
use App\Models\StructurePet;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Visitor;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class BuildClientExpedienteService
{
    /**
     * @return array<string, mixed>
     */
    public function execute(Client $client): array
    {
        $clientId = (int) $client->id;
        $today = CarbonImmutable::today();
        $from = $today->subDays(13);

        $structureByType = Structure::query()
            ->where('client_id', $clientId)
            ->selectRaw('type, COUNT(*) as aggregate')
            ->groupBy('type')
            ->pluck('aggregate', 'type');

        $structuresBreakdown = collect(StructureType::cases())
            ->map(fn (StructureType $type) => [
                'type' => $type->value,
                'label' => $type->label(),
                'count' => (int) ($structureByType[$type->value] ?? 0),
            ])
            ->filter(fn (array $row) => $row['count'] > 0)
            ->values()
            ->all();

        $memberByType = StructureMember::query()
            ->where('client_id', $clientId)
            ->selectRaw('member_type, COUNT(*) as aggregate')
            ->groupBy('member_type')
            ->pluck('aggregate', 'member_type');

        $membersBreakdown = collect(MemberType::cases())
            ->map(fn (MemberType $type) => [
                'type' => $type->value,
                'label' => $type->label(),
                'count' => (int) ($memberByType[$type->value] ?? 0),
            ])
            ->filter(fn (array $row) => $row['count'] > 0)
            ->values()
            ->all();

        $residentVehicleIds = Vehicle::query()
            ->where('client_id', $clientId)
            ->where('is_visitor_vehicle', false)
            ->pluck('id');

        $visitorVehicleIds = Vehicle::query()
            ->where('client_id', $clientId)
            ->where('is_visitor_vehicle', true)
            ->pluck('id');

        $openVehicleLogs = AccessLog::query()
            ->where('client_id', $clientId)
            ->whereNotNull('vehicle_id')
            ->whereNotNull('entry_time')
            ->whereNull('exit_time')
            ->pluck('vehicle_id')
            ->unique()
            ->values();

        $residentVehiclesInside = $openVehicleLogs->intersect($residentVehicleIds)->count();
        $visitorVehiclesInside = $openVehicleLogs->intersect($visitorVehicleIds)->count();

        $residentVehiclesRegistered = $residentVehicleIds->count();
        $visitorVehiclesRegistered = $visitorVehicleIds->count();

        $residentVehicleEntriesToday = AccessLog::query()
            ->where('client_id', $clientId)
            ->whereIn('vehicle_id', $residentVehicleIds)
            ->whereDate('entry_time', $today)
            ->count();

        $residentVehicleExitsToday = AccessLog::query()
            ->where('client_id', $clientId)
            ->whereIn('vehicle_id', $residentVehicleIds)
            ->whereDate('exit_time', $today)
            ->count();

        $entriesByDay = AccessLog::query()
            ->where('client_id', $clientId)
            ->whereNotNull('entry_time')
            ->whereDate('entry_time', '>=', $from)
            ->selectRaw('DATE(entry_time) as day, COUNT(*) as aggregate')
            ->groupBy('day')
            ->pluck('aggregate', 'day');

        $exitsByDay = AccessLog::query()
            ->where('client_id', $clientId)
            ->whereNotNull('exit_time')
            ->whereDate('exit_time', '>=', $from)
            ->selectRaw('DATE(exit_time) as day, COUNT(*) as aggregate')
            ->groupBy('day')
            ->pluck('aggregate', 'day');

        $chartLabels = [];
        $chartEntries = [];
        $chartExits = [];
        for ($i = 0; $i < 14; $i++) {
            $day = $from->addDays($i);
            $key = $day->toDateString();
            $chartLabels[] = $day->format('d/m');
            $chartEntries[] = (int) ($entriesByDay[$key] ?? 0);
            $chartExits[] = (int) ($exitsByDay[$key] ?? 0);
        }

        $guards = User::query()
            ->whereHas('clients', fn ($q) => $q->where('clients.id', $clientId))
            ->role('guardia')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $staff = User::query()
            ->whereHas('clients', fn ($q) => $q->where('clients.id', $clientId))
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'guardia'))
            ->orderBy('name')
            ->limit(12)
            ->get(['id', 'name', 'email']);

        $recentEntries = AccessLog::query()
            ->where('client_id', $clientId)
            ->with(['visitor:id,first_name,last_name', 'resident:id,first_name,last_name', 'vehicle:id,plate,is_visitor_vehicle'])
            ->latest('entry_time')
            ->limit(10)
            ->get();

        return [
            'structures_total' => (int) $structureByType->sum(),
            'structures_breakdown' => $structuresBreakdown,
            'members_total' => (int) $memberByType->sum(),
            'members_breakdown' => $membersBreakdown,
            'app_users_count' => StructureAppUser::query()->where('client_id', $clientId)->where('is_active', true)->count(),
            'pets_count' => StructurePet::query()->where('client_id', $clientId)->count(),
            'porterias_count' => Location::query()->where('client_id', $clientId)->where('type', 'porteria')->count(),
            'locations_count' => Location::query()->where('client_id', $clientId)->count(),
            'visitors_registered' => Visitor::query()->where('client_id', $clientId)->count(),
            'visitor_vehicles_registered' => $visitorVehiclesRegistered,
            'resident_vehicles_registered' => $residentVehiclesRegistered,
            'resident_vehicles_inside' => $residentVehiclesInside,
            'resident_vehicles_outside' => max(0, $residentVehiclesRegistered - $residentVehiclesInside),
            'visitor_vehicles_inside' => $visitorVehiclesInside,
            'resident_vehicle_entries_today' => $residentVehicleEntriesToday,
            'resident_vehicle_exits_today' => $residentVehicleExitsToday,
            'blocklist_active' => Blocklist::query()->where('client_id', $clientId)->where('is_active', true)->count(),
            'entries_today' => AccessLog::query()->where('client_id', $clientId)->whereDate('entry_time', $today)->count(),
            'exits_today' => AccessLog::query()->where('client_id', $clientId)->whereDate('exit_time', $today)->count(),
            'inside_now' => AccessLog::query()
                ->where('client_id', $clientId)
                ->whereNotNull('entry_time')
                ->whereNull('exit_time')
                ->count(),
            'guards' => $guards,
            'staff' => $staff,
            'recent_entries' => $recentEntries,
            'chart' => [
                'labels' => $chartLabels,
                'entries' => $chartEntries,
                'exits' => $chartExits,
            ],
            'presence_chart' => [
                'labels' => ['Dentro ahora', 'Salidas hoy', 'Bloqueados'],
                'values' => [
                    AccessLog::query()->where('client_id', $clientId)->whereNotNull('entry_time')->whereNull('exit_time')->count(),
                    AccessLog::query()->where('client_id', $clientId)->whereDate('exit_time', $today)->count(),
                    Blocklist::query()->where('client_id', $clientId)->where('is_active', true)->count(),
                ],
            ],
        ];
    }
}
