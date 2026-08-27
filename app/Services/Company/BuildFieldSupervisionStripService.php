<?php

declare(strict_types=1);

namespace App\Services\Company;

use App\Enums\SupervisorFieldOutcome;
use App\Enums\SupervisorRecommendationStatus;
use App\Enums\SupervisorShiftStatus;
use App\Models\Client;
use App\Models\SecurityCompany;
use App\Models\SupervisorFieldLog;
use App\Models\SupervisorPost;
use App\Models\SupervisorRecommendation;
use App\Models\SupervisorShift;
use App\Models\SupervisorShiftReview;

final class BuildFieldSupervisionStripService
{
    /** @return array<string, mixed>|null */
    public function forToday(SecurityCompany $company): ?array
    {
        if (! $company->hasSupervisionPackage()) {
            return null;
        }

        $companyId = (int) $company->id;
        $from = now()->startOfDay();
        $to = now()->endOfDay();

        $contracted = Client::query()
            ->where('security_company_id', $companyId)
            ->where('has_supervision', true)
            ->count();

        $visited = (int) SupervisorShiftReview::query()
            ->whereHas('shift', fn ($q) => $q->where('security_company_id', $companyId))
            ->whereBetween('recorded_at', [$from, $to])
            ->selectRaw('count(distinct client_id) as aggregate')
            ->value('aggregate');

        $openShifts = SupervisorShift::query()
            ->where('security_company_id', $companyId)
            ->where('status', SupervisorShiftStatus::Open)
            ->count();

        $reviews = SupervisorShiftReview::query()
            ->whereHas('shift', fn ($q) => $q->where('security_company_id', $companyId))
            ->whereBetween('recorded_at', [$from, $to])
            ->count();

        $km = (int) SupervisorShift::query()
            ->where('security_company_id', $companyId)
            ->whereBetween('started_at', [$from, $to])
            ->sum('km_traveled');

        $openRecs = SupervisorRecommendation::query()
            ->where('security_company_id', $companyId)
            ->whereIn('status', [
                SupervisorRecommendationStatus::Open,
                SupervisorRecommendationStatus::Progress,
            ])
            ->count();

        $attention = SupervisorFieldLog::query()
            ->where('security_company_id', $companyId)
            ->whereBetween('recorded_at', [$from, $to])
            ->where('outcome', '!=', SupervisorFieldOutcome::Ok)
            ->count();

        $coverage = $contracted > 0 ? (int) round(($visited / $contracted) * 100) : null;

        return [
            'open_shifts' => $openShifts,
            'reviews_today' => $reviews,
            'sites_contracted' => $contracted,
            'sites_visited' => $visited,
            'coverage_pct' => $coverage,
            'open_recommendations' => $openRecs,
            'attention_today' => $attention,
            'km_today' => $km,
            'posts_count' => SupervisorPost::query()
                ->whereHas('client', function ($q) use ($companyId): void {
                    $q->where('security_company_id', $companyId)
                        ->where('has_supervision', true);
                })
                ->where('is_active', true)
                ->count(),
        ];
    }
}
