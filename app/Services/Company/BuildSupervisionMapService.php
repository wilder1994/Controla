<?php

declare(strict_types=1);

namespace App\Services\Company;

use App\Domain\Supervision\Data\SupervisionQueryFilter;
use App\Enums\SupervisorShiftStatus;
use App\Models\Client;
use App\Models\SecurityCompany;
use App\Models\SupervisorShift;
use App\Models\SupervisorShiftReview;
use Carbon\CarbonImmutable;

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
            ->with([
                'user',
                'locations' => fn ($q) => $q->orderBy('recorded_at'),
            ])
            ->get()
            ->map(function (SupervisorShift $shift) {
                $built = $this->trail->execute($shift->locations, true);
                $last = $built['end'];

                return [
                    'shift_id' => $shift->id,
                    'user' => $shift->user?->name,
                    'started_at' => $shift->started_at?->toIso8601String(),
                    'lat' => $last['lat'] ?? null,
                    'lng' => $last['lng'] ?? null,
                    'recorded_at' => $last['at'] ?? null,
                    'path' => $built['path'],
                    'start' => $built['start'],
                    'end' => $built['end'],
                    'stops' => $built['stops'],
                    'parked' => $built['parked'],
                ];
            })
            ->values()
            ->all();

        $history = SupervisorShift::query()
            ->where('security_company_id', $company->id)
            ->whereBetween('started_at', [$fromAt, $toAt])
            ->matchingFilter($filter)
            ->with([
                'user',
                'locations' => fn ($q) => $q->orderBy('recorded_at'),
            ])
            ->orderByDesc('started_at')
            ->limit(40)
            ->get()
            ->map(function (SupervisorShift $shift) {
                $open = $shift->status === SupervisorShiftStatus::Open;
                $built = $this->trail->execute($shift->locations, $open);

                return [
                    'shift_id' => $shift->id,
                    'user' => $shift->user?->name,
                    'status' => $shift->status->value,
                    'started_at' => $shift->started_at?->toIso8601String(),
                    'ended_at' => $shift->ended_at?->toIso8601String(),
                    'km_traveled' => $shift->km_traveled,
                    'path' => $built['path'],
                    'start' => $built['start'],
                    'end' => $open ? null : $built['end'],
                    'stops' => $built['stops'],
                    'parked' => $built['parked'],
                ];
            })
            ->values()
            ->all();

        $reviews = SupervisorShiftReview::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereHas('shift', function ($q) use ($company, $fromAt, $toAt, $filter): void {
                $q->where('security_company_id', $company->id)
                    ->whereBetween('started_at', [$fromAt, $toAt])
                    ->matchingFilter($filter);
            })
            ->with(['shift.user', 'client:id,name', 'supervisorPost:id,name,installation_id'])
            ->orderByDesc('recorded_at')
            ->limit(200)
            ->get()
            ->map(fn (SupervisorShiftReview $review) => [
                'id' => $review->id,
                'shift_id' => (int) $review->supervisor_shift_id,
                'lat' => (float) $review->latitude,
                'lng' => (float) $review->longitude,
                'user' => $review->shift?->user?->name,
                'client' => $review->client?->name,
                'post' => $review->supervisorPost?->name,
                'novelty' => $review->has_novelty,
                'at' => $review->recorded_at?->toIso8601String(),
            ])
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
}
