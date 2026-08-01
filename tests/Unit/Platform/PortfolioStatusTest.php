<?php

declare(strict_types=1);

namespace Tests\Unit\Platform;

use App\Enums\CompanyAlertBucket;
use App\Enums\SubscriptionStatus;
use App\Models\SecurityCompany;
use App\Services\Platform\PlatformDashboardAnalytics;
use App\Support\Tenancy\CompanySubscriptionState;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PortfolioStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_suspended_is_not_overdue_bucket(): void
    {
        $this->seed();
        $company = SecurityCompany::query()->where('tax_id', '900123456-1')->firstOrFail();
        $company->update([
            'subscription_status' => SubscriptionStatus::Suspended,
            'suspended_at' => CarbonImmutable::now(),
            'archived_at' => null,
            'is_active' => false,
        ]);

        $this->assertSame(
            CompanyAlertBucket::Suspended,
            CompanySubscriptionState::bucket($company->fresh())
        );
    }

    public function test_portfolio_status_includes_six_segments(): void
    {
        $this->seed();
        $companies = SecurityCompany::query()->with('clients')->get();
        $portfolio = app(PlatformDashboardAnalytics::class)->build($companies)['portfolio_status'];

        $labels = collect($portfolio)->pluck('label')->all();

        $this->assertSame(
            ['Al día', 'Por vencer', 'Vencidos', 'Suspendidas', 'Archivadas', 'Eliminadas'],
            $labels
        );
        $this->assertCount(6, $portfolio);
    }
}
