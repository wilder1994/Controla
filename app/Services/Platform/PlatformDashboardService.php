<?php

declare(strict_types=1);

namespace App\Services\Platform;

use App\Models\SecurityCompany;
use Illuminate\Http\Request;

final class PlatformDashboardService
{
    public function __construct(
        private readonly PlatformDashboardAnalytics $analytics,
    ) {}

    /** @return array<string, mixed> */
    public function build(Request $request): array
    {
        $companies = SecurityCompany::query()
            ->with(['clients' => fn ($q) => $q->orderBy('name')])
            ->orderBy('trade_name')
            ->get();

        return [
            'analytics' => $this->analytics->build($companies),
        ];
    }
}
