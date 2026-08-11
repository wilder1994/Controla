<?php

declare(strict_types=1);

namespace App\Services\Company;

use App\Enums\ClientLifecycle;
use App\Models\AccessLog;
use App\Models\Blocklist;
use App\Models\Client;
use App\Models\Correspondence;
use App\Models\GuardLog;
use App\Models\GuardShift;
use App\Models\SecurityCompany;
use App\Models\SupervisorReview;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CompanyDashboardAnalytics
{
    /** @return array<string, mixed> */
    public function build(SecurityCompany $company): array
    {
        $companyId = (int) $company->id;

        $clients = Client::query()
            ->where('security_company_id', $companyId)
            ->orderBy('name')
            ->get();

        $activeClients = $clients
            ->filter(fn (Client $client) => $client->lifecycle === ClientLifecycle::Active)
            ->values();
        $activeClientIds = $activeClients->pluck('id')->map(static fn ($id) => (int) $id)->all();

        $archivedCount = $clients->filter(
            fn (Client $client) => $client->lifecycle === ClientLifecycle::ArchivedCompany
        )->count();

        $openShifts = $this->openShifts($activeClientIds);
        $reviewsToday = $this->reviewsOnDate($activeClientIds, today()->toDateString());
        $reviewsMonth = $this->reviewsInMonth($activeClientIds);
        $revista = $this->revistaKpis($activeClients, $reviewsToday, $reviewsMonth, $openShifts);
        $blockCounts = $this->activeBlockCounts($activeClientIds);
        $panicsToday = $this->panicsToday($activeClientIds);
        $panicsOpen = $this->panicsOpen($activeClientIds);
        $workforce = $this->workforce($companyId, $openShifts);

        $mapMarkers = $this->mapMarkers($activeClients, $reviewsToday, $openShifts);

        return [
            'kpis' => [
                'active_clients' => $activeClients->count(),
                'max_clients' => (int) ($company->max_clients ?: 0),
                'archived_clients' => $archivedCount,
                'vigilantes_on_shift' => $openShifts->pluck('user_id')->unique()->count(),
                'posts_open' => $openShifts->count(),
                'vehicle_entries_today' => $this->countAccessToday($activeClientIds, ['visitor_vehicle', 'resident_vehicle']),
                'visitor_entries_today' => $this->countAccessToday($activeClientIds, ['visitor']),
                'novedades_today' => $this->novedadesToday($activeClientIds),
                'pending_correspondence' => $this->pendingCorrespondence($activeClientIds),
                'panics_open' => $panicsOpen,
                'panics_today' => $panicsToday->count(),
                'blocklist_vehicles' => $blockCounts['vehicles'],
                'blocklist_persons' => $blockCounts['persons'],
                'revista_compliance_pct' => $revista['compliance_pct'],
                'revistas_today_done' => $revista['today_done'],
                'revistas_today_expected' => $revista['today_expected'],
                'without_revista_on_shift' => $revista['without_revista_on_shift'],
                'supervisors_on_route_today' => $revista['supervisors_on_route'],
                'supervisors_active' => $workforce['supervisors_active'],
            ],
            'access' => [
                'is_hardware' => ($company->package_modality?->value ?? 'manual') === 'hardware',
                'scope_note' => ($company->package_modality?->value ?? 'manual') === 'hardware'
                    ? 'Modalidad hardware: eventos de dispositivo + portería'
                    : 'Portería manual · residentes a pie no se contabilizan sin hardware',
            ],
            'workforce' => $workforce,
            'attention' => $this->attentionQueue(
                $activeClients,
                $panicsToday,
                $panicsOpen,
                $archivedCount,
                $blockCounts,
                $revista,
                $openShifts,
            ),
            'map_markers' => $mapMarkers,
            'google_maps' => [
                'api_key' => config('google-maps.api_key'),
                'center' => config('google-maps.default_center'),
                'zoom' => config('google-maps.default_zoom'),
            ],
            'compliance_by_client' => $this->complianceByClient($activeClients, $reviewsMonth),
            'revista_trend' => $this->revistaTrend($activeClients, $activeClientIds),
            'access_by_client' => $this->accessByClientToday($activeClients),
            'open_shifts_table' => $this->openShiftsTable($openShifts, $reviewsToday),
            'portfolio' => [
                'with_geo' => $activeClients->filter(
                    fn (Client $c) => $c->latitude !== null && $c->longitude !== null
                )->count(),
                'active_total' => $activeClients->count(),
            ],
        ];
    }

    /**
     * @param  list<int>  $clientIds
     * @return Collection<int, GuardShift>
     */
    private function openShifts(array $clientIds): Collection
    {
        if ($clientIds === []) {
            return collect();
        }

        return GuardShift::query()
            ->with(['user', 'location', 'client'])
            ->whereIn('client_id', $clientIds)
            ->whereNull('ended_at')
            ->orderByDesc('started_at')
            ->get();
    }

    /**
     * @param  list<int>  $clientIds
     * @return Collection<int, SupervisorReview>
     */
    private function reviewsOnDate(array $clientIds, string $date): Collection
    {
        if ($clientIds === []) {
            return collect();
        }

        return SupervisorReview::query()
            ->whereIn('client_id', $clientIds)
            ->whereDate('reviewed_at', $date)
            ->get();
    }

    /**
     * @param  list<int>  $clientIds
     * @return Collection<int, SupervisorReview>
     */
    private function reviewsInMonth(array $clientIds): Collection
    {
        if ($clientIds === []) {
            return collect();
        }

        return SupervisorReview::query()
            ->whereIn('client_id', $clientIds)
            ->whereBetween('reviewed_at', [now()->startOfMonth()->copy(), now()->endOfMonth()->copy()])
            ->get();
    }

    /**
     * @param  Collection<int, Client>  $activeClients
     * @param  Collection<int, SupervisorReview>  $reviewsToday
     * @param  Collection<int, SupervisorReview>  $reviewsMonth
     * @param  Collection<int, GuardShift>  $openShifts
     * @return array{
     *     compliance_pct: float,
     *     today_done: int,
     *     today_expected: int,
     *     month_done: int,
     *     month_expected: int,
     *     without_revista_on_shift: int,
     *     supervisors_on_route: int
     * }
     */
    private function revistaKpis(
        Collection $activeClients,
        Collection $reviewsToday,
        Collection $reviewsMonth,
        Collection $openShifts,
    ): array {
        $todayExpected = (int) $activeClients->sum(
            fn (Client $c) => max(1, (int) ($c->revista_target_per_day ?: 1))
        );
        $dayOfMonth = (int) now()->day;
        $monthExpected = (int) $activeClients->sum(
            fn (Client $c) => max(1, (int) ($c->revista_target_per_day ?: 1)) * $dayOfMonth
        );

        $todayDone = $reviewsToday->count();
        $monthDone = $reviewsMonth->count();
        $compliance = $monthExpected > 0
            ? round(($monthDone / $monthExpected) * 100, 1)
            : 0.0;

        $withoutRevista = 0;
        foreach ($openShifts as $shift) {
            $hasReview = $reviewsToday->contains(function (SupervisorReview $review) use ($shift): bool {
                if ($review->guard_shift_id !== null && (int) $review->guard_shift_id === (int) $shift->id) {
                    return true;
                }

                return (int) $review->client_id === (int) $shift->client_id
                    && $review->reviewed_at !== null
                    && $review->reviewed_at->gte($shift->started_at)
                    && (
                        $review->guard_user_id === null
                        || (int) $review->guard_user_id === (int) $shift->user_id
                    );
            });

            if (! $hasReview) {
                $withoutRevista++;
            }
        }

        return [
            'compliance_pct' => $compliance,
            'today_done' => $todayDone,
            'today_expected' => $todayExpected,
            'month_done' => $monthDone,
            'month_expected' => $monthExpected,
            'without_revista_on_shift' => $withoutRevista,
            'supervisors_on_route' => $reviewsToday->pluck('supervisor_id')->unique()->count(),
        ];
    }

    /**
     * @param  list<int>  $clientIds
     * @param  list<string>  $types
     */
    private function countAccessToday(array $clientIds, array $types): int
    {
        if ($clientIds === []) {
            return 0;
        }

        return (int) AccessLog::query()
            ->whereIn('client_id', $clientIds)
            ->whereDate('entry_time', today())
            ->whereIn('access_type', $types)
            ->count();
    }

    /**
     * @param  list<int>  $clientIds
     * @return array{vehicles: int, persons: int}
     */
    private function activeBlockCounts(array $clientIds): array
    {
        if ($clientIds === []) {
            return ['vehicles' => 0, 'persons' => 0];
        }

        $base = Blocklist::query()->whereIn('client_id', $clientIds)->active();

        return [
            'vehicles' => (clone $base)->vehicles()->count(),
            'persons' => (clone $base)->persons()->count(),
        ];
    }

    /**
     * @param  list<int>  $clientIds
     * @return Collection<int, GuardLog>
     */
    private function panicsToday(array $clientIds): Collection
    {
        if ($clientIds === []) {
            return collect();
        }

        return GuardLog::query()
            ->with(['user', 'location', 'client'])
            ->whereIn('client_id', $clientIds)
            ->where('is_panic', true)
            ->whereDate('log_time', today())
            ->orderByDesc('log_time')
            ->get();
    }

    /** @param  list<int>  $clientIds */
    private function panicsOpen(array $clientIds): int
    {
        if ($clientIds === []) {
            return 0;
        }

        return (int) GuardLog::query()
            ->whereIn('client_id', $clientIds)
            ->where('is_panic', true)
            ->whereNull('resolved_at')
            ->count();
    }

    /** @param  list<int>  $clientIds */
    private function pendingCorrespondence(array $clientIds): int
    {
        if ($clientIds === []) {
            return 0;
        }

        return (int) Correspondence::query()
            ->whereIn('client_id', $clientIds)
            ->where('status', 'pending')
            ->count();
    }

    /** @param  list<int>  $clientIds */
    private function novedadesToday(array $clientIds): int
    {
        if ($clientIds === []) {
            return 0;
        }

        return (int) GuardLog::query()
            ->whereIn('client_id', $clientIds)
            ->where('is_panic', false)
            ->whereIn('type', ['novedad', 'incidente', 'general'])
            ->whereDate('log_time', today())
            ->count();
    }

    /**
     * @param  Collection<int, GuardShift>  $openShifts
     * @return array{vigilantes_active: int, vigilantes_on_shift: int, supervisors_active: int, without_assignment: int}
     */
    private function workforce(int $companyId, Collection $openShifts): array
    {
        $vigilantes = User::query()
            ->where('security_company_id', $companyId)
            ->where('is_active', true)
            ->role('guardia')
            ->withCount('clients')
            ->get();

        $supervisors = User::query()
            ->where('security_company_id', $companyId)
            ->where('is_active', true)
            ->role('supervisor')
            ->count();

        return [
            'vigilantes_active' => $vigilantes->count(),
            'vigilantes_on_shift' => $openShifts->pluck('user_id')->unique()->count(),
            'supervisors_active' => $supervisors,
            'without_assignment' => $vigilantes->where('clients_count', 0)->count(),
        ];
    }

    /**
     * @param  Collection<int, Client>  $activeClients
     * @param  Collection<int, GuardLog>  $panicsToday
     * @param  array{vehicles: int, persons: int}  $blockCounts
     * @param  array<string, mixed>  $revista
     * @param  Collection<int, GuardShift>  $openShifts
     * @return list<array{priority: string, tone: string, signal: string, context: string}>
     */
    private function attentionQueue(
        Collection $activeClients,
        Collection $panicsToday,
        int $panicsOpen,
        int $archivedCount,
        array $blockCounts,
        array $revista,
        Collection $openShifts,
    ): array {
        $items = [];

        foreach ($panicsToday as $panic) {
            $items[] = [
                'priority' => 'Pánico',
                'tone' => 'danger',
                'signal' => Str::limit((string) $panic->description, 60),
                'context' => $panic->client?->name ?? $panic->location?->name ?? '—',
            ];
        }

        if ($panicsOpen > 0 && $panicsToday->isEmpty()) {
            $items[] = [
                'priority' => 'Pánico',
                'tone' => 'danger',
                'signal' => sprintf('%d pánico(s) abierto(s) sin cerrar', $panicsOpen),
                'context' => 'Operación',
            ];
        }

        $reviewsToday = $this->reviewsOnDate(
            $activeClients->pluck('id')->map(static fn ($id) => (int) $id)->all(),
            today()->toDateString()
        );

        foreach ($openShifts as $shift) {
            $hasReview = $reviewsToday->contains(function (SupervisorReview $review) use ($shift): bool {
                if ($review->guard_shift_id !== null && (int) $review->guard_shift_id === (int) $shift->id) {
                    return true;
                }

                return (int) $review->client_id === (int) $shift->client_id
                    && $review->reviewed_at !== null
                    && $review->reviewed_at->gte($shift->started_at);
            });

            if (! $hasReview) {
                $items[] = [
                    'priority' => 'Sin revista',
                    'tone' => 'warning',
                    'signal' => 'Turno abierto sin firma de revista',
                    'context' => $shift->client?->name
                        ?? $shift->location?->name
                        ?? '—',
                ];
            }
        }

        foreach ($activeClients as $client) {
            if ($client->latitude === null || $client->longitude === null) {
                $items[] = [
                    'priority' => 'Sin geo',
                    'tone' => 'neutral',
                    'signal' => 'Cliente sin coordenadas en mapa',
                    'context' => $client->name,
                ];
            }
        }

        if ($archivedCount > 0) {
            $items[] = [
                'priority' => 'Archivado',
                'tone' => 'neutral',
                'signal' => sprintf('%d conjunto(s) archivado(s) · no consumen cupo', $archivedCount),
                'context' => 'Cartera',
            ];
        }

        $blockTotal = $blockCounts['vehicles'] + $blockCounts['persons'];
        if ($blockTotal > 0) {
            $items[] = [
                'priority' => 'Lista bloqueo',
                'tone' => 'warning',
                'signal' => sprintf(
                    'Vehículos %d · Personas %d activos',
                    $blockCounts['vehicles'],
                    $blockCounts['persons']
                ),
                'context' => 'Todos los conjuntos',
            ];
        }

        return array_slice($items, 0, 8);
    }

    /**
     * @param  Collection<int, Client>  $activeClients
     * @param  Collection<int, SupervisorReview>  $reviewsToday
     * @param  Collection<int, GuardShift>  $openShifts
     * @return list<array<string, mixed>>
     */
    private function mapMarkers(
        Collection $activeClients,
        Collection $reviewsToday,
        Collection $openShifts,
    ): array {
        $markers = [];

        foreach ($activeClients as $client) {
            if ($client->latitude === null || $client->longitude === null) {
                continue;
            }

            $target = max(1, (int) ($client->revista_target_per_day ?: 1));
            $done = $reviewsToday->where('client_id', $client->id)->count();
            $hasOpenShift = $openShifts->contains(fn (GuardShift $s) => (int) $s->client_id === (int) $client->id);
            $tone = 'ok';
            if ($done === 0 && $hasOpenShift) {
                $tone = 'danger';
            } elseif ($done < $target) {
                $tone = 'warn';
            }

            $markers[] = [
                'id' => (int) $client->id,
                'lat' => (float) $client->latitude,
                'lng' => (float) $client->longitude,
                'title' => $client->name,
                'tone' => $tone,
                'tone_label' => match ($tone) {
                    'ok' => 'Salud OK',
                    'warn' => 'Bajo meta',
                    default => 'Crítico',
                },
                'revistas_hoy' => sprintf('%d/%d', $done, $target),
                'turno_abierto' => $hasOpenShift,
                'vehiculos_hoy' => $this->countAccessToday([(int) $client->id], ['visitor_vehicle', 'resident_vehicle']),
                'visitantes_hoy' => $this->countAccessToday([(int) $client->id], ['visitor']),
                'service_started' => $client->service_started_at?->format('d/m/Y') ?? '—',
                'ultima_novedad' => $this->lastNovedadLabel((int) $client->id),
                'url' => route('company.clients.show', $client),
                'operate_url' => route('company.clients.activate', $client),
            ];
        }

        return $markers;
    }

    private function lastNovedadLabel(int $clientId): string
    {
        $log = GuardLog::query()
            ->where('client_id', $clientId)
            ->where('is_panic', false)
            ->whereIn('type', ['novedad', 'incidente', 'general', 'turno'])
            ->orderByDesc('log_time')
            ->first();

        if ($log === null) {
            return 'Sin novedades';
        }

        return sprintf(
            '%s · %s',
            $log->log_time?->format('H:i') ?? '—',
            Str::limit((string) $log->description, 40)
        );
    }

    /**
     * @param  Collection<int, Client>  $activeClients
     * @param  Collection<int, SupervisorReview>  $reviewsMonth
     * @return list<array{label: string, value: float}>
     */
    private function complianceByClient(Collection $activeClients, Collection $reviewsMonth): array
    {
        $dayOfMonth = max(1, (int) now()->day);
        $rows = [];

        foreach ($activeClients as $client) {
            $targetDay = max(1, (int) ($client->revista_target_per_day ?: 1));
            $expected = $targetDay * $dayOfMonth;
            $done = $reviewsMonth->where('client_id', $client->id)->count();
            $pct = $expected > 0 ? round(($done / $expected) * 100, 1) : 0.0;
            $rows[] = [
                'label' => $client->name,
                'value' => min(100.0, $pct),
            ];
        }

        usort($rows, static fn (array $a, array $b): int => $b['value'] <=> $a['value']);

        return array_slice($rows, 0, 5);
    }

    /**
     * @param  Collection<int, Client>  $activeClients
     * @param  list<int>  $clientIds
     * @return array{labels: list<string>, done: list<int>, expected: list<int>}
     */
    private function revistaTrend(Collection $activeClients, array $clientIds): array
    {
        $labels = [];
        $done = [];
        $expected = [];
        $dailyExpected = (int) $activeClients->sum(
            fn (Client $c) => max(1, (int) ($c->revista_target_per_day ?: 1))
        );

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->translatedFormat('D');
            $expected[] = $dailyExpected;
            $done[] = $clientIds === []
                ? 0
                : (int) SupervisorReview::query()
                    ->whereIn('client_id', $clientIds)
                    ->whereDate('reviewed_at', $date->toDateString())
                    ->count();
        }

        return [
            'labels' => $labels,
            'done' => $done,
            'expected' => $expected,
        ];
    }

    /**
     * @param  Collection<int, Client>  $activeClients
     * @return list<array{label: string, vehicles: int, visitors: int}>
     */
    private function accessByClientToday(Collection $activeClients): array
    {
        if ($activeClients->isEmpty()) {
            return [];
        }

        $ids = $activeClients->pluck('id')->all();
        $rows = AccessLog::query()
            ->select('client_id', 'access_type', DB::raw('COUNT(*) as total'))
            ->whereIn('client_id', $ids)
            ->whereDate('entry_time', today())
            ->whereIn('access_type', ['visitor', 'visitor_vehicle', 'resident_vehicle'])
            ->groupBy('client_id', 'access_type')
            ->get();

        $byClient = [];
        foreach ($activeClients as $client) {
            $byClient[(int) $client->id] = [
                'label' => $client->name,
                'vehicles' => 0,
                'visitors' => 0,
            ];
        }

        foreach ($rows as $row) {
            $id = (int) $row->client_id;
            if (! isset($byClient[$id])) {
                continue;
            }
            if ($row->access_type === 'visitor') {
                $byClient[$id]['visitors'] += (int) $row->total;
            } else {
                $byClient[$id]['vehicles'] += (int) $row->total;
            }
        }

        return collect($byClient)
            ->sortByDesc(fn (array $row) => $row['vehicles'] + $row['visitors'])
            ->take(5)
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, GuardShift>  $openShifts
     * @param  Collection<int, SupervisorReview>  $reviewsToday
     * @return list<array{puesto: string, vigilante: string, inicio: string, ultima_revista: string, tone: string}>
     */
    private function openShiftsTable(Collection $openShifts, Collection $reviewsToday): array
    {
        $rows = [];

        foreach ($openShifts->take(8) as $shift) {
            $review = $reviewsToday
                ->filter(fn (SupervisorReview $r) => (int) $r->client_id === (int) $shift->client_id)
                ->sortByDesc(fn (SupervisorReview $r) => $r->reviewed_at?->timestamp ?? 0)
                ->first();

            $hasReview = $review !== null
                && $review->reviewed_at !== null
                && $review->reviewed_at->gte($shift->started_at);

            $rows[] = [
                'puesto' => $shift->location?->name ?? $shift->client?->name ?? '—',
                'vigilante' => $shift->user?->name ?? '—',
                'inicio' => $shift->started_at?->format('H:i') ?? '—',
                'ultima_revista' => $hasReview ? ($review->reviewed_at?->format('H:i') ?? '—') : '—',
                'tone' => $hasReview ? 'success' : 'danger',
            ];
        }

        return $rows;
    }
}
