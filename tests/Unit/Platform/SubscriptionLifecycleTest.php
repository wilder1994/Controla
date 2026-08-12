<?php

declare(strict_types=1);

namespace Tests\Unit\Platform;

use App\Enums\ArchiveReason;
use App\Enums\SubscriptionStatus;
use App\Models\SecurityCompany;
use App\Services\Platform\ProcessSubscriptionLifecycleService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SubscriptionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_past_due_enters_grace_with_configured_days(): void
    {
        config(['subscription.grace_days' => 5]);

        $this->seed();
        $company = SecurityCompany::query()->where('tax_id', '900123456-1')->firstOrFail();
        $endsAt = CarbonImmutable::parse('2026-07-01');

        $company->update([
            'subscription_status' => SubscriptionStatus::Active,
            'package_ends_at' => $endsAt,
            'grace_ends_at' => null,
            'suspended_at' => null,
            'archived_at' => null,
            'is_active' => true,
        ]);

        $now = CarbonImmutable::parse('2026-07-02');
        $processed = app(ProcessSubscriptionLifecycleService::class)->execute($now);

        $this->assertSame(1, $processed);
        $company->refresh();
        $this->assertSame(SubscriptionStatus::Grace, $company->subscription_status);
        $this->assertTrue($company->grace_ends_at->equalTo($endsAt->addDays(5)));
        $this->assertTrue($company->is_active);
        $this->assertNull($company->archived_at);
    }

    public function test_grace_expired_suspends_without_archiving(): void
    {
        config(['subscription.grace_days' => 5]);

        $this->seed();
        $company = SecurityCompany::query()->where('tax_id', '900123456-1')->firstOrFail();

        $company->update([
            'subscription_status' => SubscriptionStatus::Grace,
            'package_ends_at' => CarbonImmutable::parse('2026-07-01'),
            'grace_ends_at' => CarbonImmutable::parse('2026-07-06'),
            'suspended_at' => null,
            'archived_at' => null,
            'is_active' => true,
        ]);

        $processed = app(ProcessSubscriptionLifecycleService::class)
            ->execute(CarbonImmutable::parse('2026-07-07'));

        $this->assertSame(1, $processed);
        $company->refresh();
        $this->assertSame(SubscriptionStatus::Suspended, $company->subscription_status);
        $this->assertFalse($company->is_active);
        $this->assertNotNull($company->suspended_at);
        $this->assertNull($company->archived_at);
        $this->assertNull($company->archive_reason);
    }

    public function test_long_suspension_archives_for_non_payment(): void
    {
        config([
            'subscription.grace_days' => 5,
            'subscription.archive_after_suspended_days' => 90,
        ]);

        $this->seed();
        $company = SecurityCompany::query()->where('tax_id', '900123456-1')->firstOrFail();

        $company->update([
            'subscription_status' => SubscriptionStatus::Suspended,
            'package_ends_at' => CarbonImmutable::parse('2026-01-01'),
            'suspended_at' => CarbonImmutable::parse('2026-01-10'),
            'archived_at' => null,
            'archive_reason' => null,
            'is_active' => false,
        ]);

        $processed = app(ProcessSubscriptionLifecycleService::class)
            ->execute(CarbonImmutable::parse('2026-04-15'));

        $this->assertSame(1, $processed);
        $company->refresh();
        $this->assertNotNull($company->archived_at);
        $this->assertSame(ArchiveReason::NonPayment, $company->archive_reason);
        $this->assertSame(SubscriptionStatus::Suspended, $company->subscription_status);
    }

    public function test_does_not_use_recovery_archive_reason(): void
    {
        $this->assertFalse(
            collect(ArchiveReason::cases())->contains(fn (ArchiveReason $r) => $r->value === 'recovery')
        );
        $this->assertSame('Falta de pago', ArchiveReason::NonPayment->label());
    }
}
