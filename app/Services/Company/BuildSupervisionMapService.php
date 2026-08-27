<?php

declare(strict_types=1);

namespace App\Services\Company;

use App\Domain\Supervision\Data\SupervisionQueryFilter;
use App\Enums\SupervisorShiftStatus;
use App\Models\SecurityCompany;
use App\Models\SupervisorShift;
use App\Models\SupervisorShiftReview;
use Carbon\CarbonImmutable;

final class BuildSupervisionMapService
{
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
                'locations' => fn ($q) => $q->orderByDesc('recorded_at')->limit(1),
                'reviews' => fn ($q) => $q->whereNotNull('latitude')->whereNotNull('longitude'),
            ])
            ->get()
            ->map(function (SupervisorShift $shift) {
                $last = $shift->locations->first();

                return [
                    'shift_id' => $shift->id,
                    'user' => $shift->user?->name,
                    'started_at' => $shift->started_at?->toIso8601String(),
                    'lat' => $last !== null ? (float) $last->latitude : null,
                    'lng' => $last !== null ? (float) $last->longitude : null,
                    'recorded_at' => $last?->recorded_at?->toIso8601String(),
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
                'reviews',
            ])
            ->orderByDesc('started_at')
            ->limit(40)
            ->get()
            ->map(function (SupervisorShift $shift) {
                $path = $shift->locations
                    ->map(fn ($loc) => [
                        'lat' => (float) $loc->latitude,
                        'lng' => (float) $loc->longitude,
                        'at' => $loc->recorded_at?->toIso8601String(),
                    ])
                    ->values()
                    ->all();

                return [
                    'shift_id' => $shift->id,
                    'user' => $shift->user?->name,
                    'status' => $shift->status->value,
                    'started_at' => $shift->started_at?->toIso8601String(),
                    'ended_at' => $shift->ended_at?->toIso8601String(),
                    'km_traveled' => $shift->km_traveled,
                    'path' => $path,
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

        return [
            'live' => $live,
            'history' => $history,
            'reviews' => $reviews,
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
