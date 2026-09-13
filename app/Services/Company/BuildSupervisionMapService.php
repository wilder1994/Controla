<?php

declare(strict_types=1);

namespace App\Services\Company;

use App\Domain\Supervision\Data\SupervisionQueryFilter;
use App\Enums\SupervisorFieldModule;
use App\Enums\SupervisorFieldSheetKind;
use App\Enums\SupervisorShiftStatus;
use App\Models\Client;
use App\Models\SecurityCompany;
use App\Models\SupervisorFieldLog;
use App\Models\SupervisorShift;
use App\Models\SupervisorShiftReview;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

final class BuildSupervisionMapService
{
    public function __construct(
        private readonly BuildSupervisorTrailService $trail,
    ) {}

    /** @return array<string, mixed> */
    public function execute(SecurityCompany $company, SupervisionQueryFilter $filter): array
    {
        $fromAt = $filter->from !== null && $filter->from !== ''
            ? CarbonImmutable::parse($filter->from)->startOfDay()
            : CarbonImmutable::now()->subDay()->startOfDay();
        $toAt = $filter->to !== null && $filter->to !== ''
            ? CarbonImmutable::parse($filter->to)->endOfDay()
            : CarbonImmutable::now()->endOfDay();

        $live = SupervisorShift::query()
            ->where('security_company_id', $company->id)
            ->where('status', SupervisorShiftStatus::Open)
            ->matchingFilter($filter)
            ->withCount('reviews')
            ->with([
                'user',
                'locations' => fn ($q) => $q->orderBy('recorded_at'),
            ])
            ->get()
            ->map(fn (SupervisorShift $shift) => $this->mapLiveShift($shift))
            ->values()
            ->all();

        $history = SupervisorShift::query()
            ->where('security_company_id', $company->id)
            ->whereBetween('started_at', [$fromAt, $toAt])
            ->matchingFilter($filter)
            ->with([
                'user',
                'shiftTemplate',
                'locations' => fn ($q) => $q->orderBy('recorded_at'),
            ])
            ->orderByDesc('started_at')
            ->limit(40)
            ->get()
            ->map(function (SupervisorShift $shift) {
                $open = $shift->status === SupervisorShiftStatus::Open;
                $built = $this->trail->execute($shift->locations, $open);
                $tz = (string) config('app.timezone');
                $started = $shift->started_at;
                $ended = $shift->ended_at;
                $auto = (bool) $shift->closed_by_system || str_contains((string) $shift->notes, 'Cierre automático');
                $queued = (int) ($shift->pending_outbox_count ?? 0);
                $statusLabel = 'Cerrado';
                if ($open) {
                    $statusLabel = 'Abierto';
                } elseif ($auto) {
                    $statusLabel = 'Cierre por el sistema';
                    if ($queued > 0) {
                        $statusLabel .= ' · '.$queued.' registro'.($queued === 1 ? '' : 's').' en cola';
                    }
                }

                return [
                    'shift_id' => $shift->id,
                    'user' => $shift->user?->name,
                    'status' => $shift->status->value,
                    'status_label' => $statusLabel,
                    'closed_by_system' => $auto && ! $open,
                    'pending_outbox_count' => $queued,
                    'started_at' => $started?->toIso8601String(),
                    'started_at_label' => $started?->timezone($tz)->format('d/m H:i'),
                    'ended_at' => $ended?->toIso8601String(),
                    'ended_at_label' => $ended?->timezone($tz)->format('d/m H:i'),
                    'schedule_label' => $shift->schedule_label ?? $shift->shiftTemplate?->scheduleLabel(),
                    'km_traveled' => $shift->km_traveled ?? $built['km'],
                    'path' => $built['path'],
                    'start' => $built['start'],
                    'end' => $built['end'],
                    'stops' => $built['stops'],
                    'parked' => $built['parked'],
                    'route_cached' => is_array($shift->snapped_route) && $shift->snapped_route !== [],
                ];
            })
            ->values()
            ->all();

        $shiftScope = function ($q) use ($company, $fromAt, $toAt, $filter): void {
            $q->where('security_company_id', $company->id)
                ->whereBetween('started_at', [$fromAt, $toAt])
                ->matchingFilter($filter);
        };

        $reviews = SupervisorShiftReview::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereHas('shift', $shiftScope)
            ->with(['shift.user', 'client:id,name', 'supervisorPost:id,name', 'employee'])
            ->orderByDesc('recorded_at')
            ->limit(200)
            ->get()
            ->map(fn (SupervisorShiftReview $review) => $this->mapReview($review))
            ->values()
            ->all();

        $events = SupervisorFieldLog::query()
            ->whereIn('module', [SupervisorFieldModule::Alarms, SupervisorFieldModule::Supports])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereHas('shift', $shiftScope)
            ->with(['shift.user', 'user', 'client:id,name'])
            ->orderByDesc('recorded_at')
            ->limit(200)
            ->get()
            ->map(fn (SupervisorFieldLog $log) => $this->mapFieldEvent($log))
            ->values()
            ->all();

        $clients = Client::query()
            ->where('security_company_id', $company->id)
            ->where('has_supervision', true)
            ->where('is_active', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('name')
            ->get(['id', 'name', 'latitude', 'longitude'])
            ->map(fn (Client $client) => [
                'id' => $client->id,
                'name' => $client->name,
                'lat' => (float) $client->latitude,
                'lng' => (float) $client->longitude,
            ])
            ->values()
            ->all();

        return [
            'live' => $live,
            'history' => $history,
            'reviews' => $reviews,
            'events' => $events,
            'clients' => $clients,
            'from' => $fromAt->toDateString(),
            'to' => $toAt->toDateString(),
            'google_maps' => [
                'api_key' => config('google-maps.api_key'),
                'center' => config('google-maps.default_center'),
                'zoom' => config('google-maps.default_zoom'),
            ],
        ];
    }

    /** @return array{live: list<array<string, mixed>>, reviews: list<array<string, mixed>>, events: list<array<string, mixed>>} */
    public function liveFeed(SecurityCompany $company, SupervisionQueryFilter $filter): array
    {
        $live = SupervisorShift::query()
            ->where('security_company_id', $company->id)
            ->where('status', SupervisorShiftStatus::Open)
            ->matchingFilter($filter)
            ->withCount('reviews')
            ->with([
                'user',
                'locations' => fn ($q) => $q->orderBy('recorded_at'),
            ])
            ->get()
            ->map(fn (SupervisorShift $shift) => $this->mapLiveShift($shift))
            ->values()
            ->all();

        $shiftIds = array_column($live, 'shift_id');
        $reviews = $shiftIds === []
            ? []
            : SupervisorShiftReview::query()
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->whereIn('supervisor_shift_id', $shiftIds)
                ->with(['shift.user', 'client:id,name', 'supervisorPost:id,name', 'employee'])
                ->orderByDesc('recorded_at')
                ->limit(200)
                ->get()
                ->map(fn (SupervisorShiftReview $review) => $this->mapReview($review))
                ->values()
                ->all();

        $events = $shiftIds === []
            ? []
            : SupervisorFieldLog::query()
                ->whereIn('module', [SupervisorFieldModule::Alarms, SupervisorFieldModule::Supports])
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->whereIn('supervisor_shift_id', $shiftIds)
                ->with(['shift.user', 'user', 'client:id,name'])
                ->orderByDesc('recorded_at')
                ->limit(200)
                ->get()
                ->map(fn (SupervisorFieldLog $log) => $this->mapFieldEvent($log))
                ->values()
                ->all();

        return [
            'live' => $live,
            'reviews' => $reviews,
            'events' => $events,
        ];
    }

    /** @return array<string, mixed> */
    private function mapLiveShift(SupervisorShift $shift): array
    {
        $built = $this->trail->execute($shift->locations, true);
        $last = $built['end'];
        $tz = (string) config('app.timezone');
        $started = $shift->started_at;
        $lastAt = isset($last['at']) ? CarbonImmutable::parse($last['at']) : null;
        $parked = $built['parked'];
        if (is_array($parked) && $lastAt !== null) {
            $parked['last_gps_label'] = $lastAt->timezone($tz)->format('H:i');
            $parked['label'] = ($parked['label'] ?? '').' · último GPS '.$parked['last_gps_label'];
        }

        $statusLine = 'Sin GPS aún';
        if ($parked !== null) {
            $statusLine = (string) ($parked['label'] ?? 'Detenido');
        } elseif ($lastAt !== null) {
            $statusLine = 'En ruta · último GPS '.$lastAt->timezone($tz)->format('H:i');
        }

        return [
            'shift_id' => $shift->id,
            'user' => $shift->user?->name,
            'started_at' => $started?->toIso8601String(),
            'started_at_label' => $started?->timezone($tz)->format('d/m H:i'),
            'lat' => $last['lat'] ?? null,
            'lng' => $last['lng'] ?? null,
            'recorded_at' => $last['at'] ?? null,
            'path' => $built['path'],
            'start' => $built['start'],
            'end' => $built['end'],
            'stops' => $built['stops'],
            'parked' => $parked,
            'km' => $built['km'],
            'reviews_count' => (int) ($shift->reviews_count ?? 0),
            'online' => $built['online'],
            'signal' => $built['signal'],
            'online_label' => $built['online_label'],
            'status_line' => $statusLine,
        ];
    }

    /** @return array<string, mixed> */
    private function mapReview(SupervisorShiftReview $review): array
    {
        $at = $review->recorded_at;
        $kind = SupervisorFieldSheetKind::Review;

        return [
            'id' => $review->id,
            'shift_id' => (int) $review->supervisor_shift_id,
            'lat' => (float) $review->latitude,
            'lng' => (float) $review->longitude,
            'user' => $review->shift?->user?->name,
            'client' => $review->client?->name,
            'post' => $review->supervisorPost?->name,
            'guard' => $review->employee?->fullName(),
            'novelty' => $review->has_novelty,
            'notes' => $review->notes !== null && $review->notes !== ''
                ? Str::limit($review->notes, 140)
                : null,
            'folio' => $at !== null ? $kind->folio((int) $review->id, $at) : null,
            'sheet_url' => route('company.supervision.sheets.show', [
                'kind' => $kind->value,
                'id' => $review->id,
            ]),
            'at' => $at?->toIso8601String(),
            'at_label' => $at?->timezone(config('app.timezone'))->format('d/m H:i'),
        ];
    }

    /** @return array<string, mixed> */
    private function mapFieldEvent(SupervisorFieldLog $log): array
    {
        $at = $log->recorded_at;
        $sheetKind = SupervisorFieldSheetKind::fromStandaloneModule($log->module);
        $payload = is_array($log->payload) ? $log->payload : [];
        $pinKind = $log->module === SupervisorFieldModule::Alarms ? 'alarm' : 'support';
        $subtitle = $pinKind === 'alarm'
            ? (string) ($payload['alarm_type'] ?? '')
            : (string) ($payload['support_type'] ?? '');
        $notes = $pinKind === 'support'
            ? (string) ($payload['reason'] ?? $log->notes ?? '')
            : (string) ($log->notes ?? '');

        return [
            'id' => $log->id,
            'kind' => $pinKind,
            'shift_id' => (int) $log->supervisor_shift_id,
            'lat' => (float) $log->latitude,
            'lng' => (float) $log->longitude,
            'user' => $log->user?->name ?? $log->shift?->user?->name,
            'client' => $log->client?->name,
            'title' => $sheetKind?->label() ?? $log->module->label(),
            'subtitle' => $subtitle !== '' ? $subtitle : null,
            'outcome' => $log->outcome?->value,
            'outcome_label' => $log->outcome?->label(),
            'notes' => $notes !== '' ? Str::limit($notes, 140) : null,
            'folio' => ($sheetKind !== null && $at !== null) ? $sheetKind->folio((int) $log->id, $at) : null,
            'sheet_url' => $sheetKind !== null
                ? route('company.supervision.sheets.show', [
                    'kind' => $sheetKind->value,
                    'id' => $log->id,
                ])
                : null,
            'at' => $at?->toIso8601String(),
            'at_label' => $at?->timezone(config('app.timezone'))->format('d/m H:i'),
        ];
    }
}
