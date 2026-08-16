<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use App\Enums\ArchiveReason;
use App\Enums\CompanyAlertBucket;
use App\Enums\SubscriptionStatus;
use App\Models\SecurityCompany;
use Carbon\CarbonImmutable;

final class CompanySubscriptionState
{
    private const DUE_SOON_DAYS = 30;

    public static function bucket(SecurityCompany $company, ?CarbonImmutable $now = null): CompanyAlertBucket
    {
        $now ??= CarbonImmutable::now();

        if ($company->archived_at !== null) {
            return CompanyAlertBucket::Archived;
        }

        // Suspendida = acceso bloqueado, aún no archivada (distinto de gracia/vencidos).
        if ($company->subscription_status === SubscriptionStatus::Suspended) {
            return CompanyAlertBucket::Suspended;
        }

        // Cancelada al fin de periodo: sigue vigente hasta package_ends_at.
        if ($company->subscription_status === SubscriptionStatus::Cancelled) {
            $endsAt = $company->package_ends_at;
            if ($endsAt !== null && ! $endsAt->isPast()) {
                $daysLeft = (int) $now->startOfDay()->diffInDays($endsAt->startOfDay(), false);

                return $daysLeft <= self::DUE_SOON_DAYS
                    ? CompanyAlertBucket::DueSoon
                    : CompanyAlertBucket::Current;
            }

            return CompanyAlertBucket::Overdue;
        }

        if ($company->subscription_status === SubscriptionStatus::Expired) {
            return CompanyAlertBucket::Overdue;
        }

        if ($company->subscription_status === SubscriptionStatus::Grace) {
            return CompanyAlertBucket::Overdue;
        }

        $endsAt = $company->package_ends_at;

        if ($endsAt === null) {
            return CompanyAlertBucket::Current;
        }

        if ($endsAt->isPast()) {
            return CompanyAlertBucket::Overdue;
        }

        $daysLeft = (int) $now->startOfDay()->diffInDays($endsAt->startOfDay(), false);

        if ($daysLeft <= self::DUE_SOON_DAYS) {
            return CompanyAlertBucket::DueSoon;
        }

        return CompanyAlertBucket::Current;
    }

    public static function daysUntilRenewal(SecurityCompany $company, ?CarbonImmutable $now = null): ?int
    {
        $now ??= CarbonImmutable::now();
        $endsAt = $company->package_ends_at;

        if ($endsAt === null) {
            return null;
        }

        return (int) $now->startOfDay()->diffInDays($endsAt->startOfDay(), false);
    }

    public static function matchesArchiveFilter(SecurityCompany $company, ?ArchiveReason $reason): bool
    {
        if ($company->archived_at === null) {
            return false;
        }

        if ($reason === null) {
            return true;
        }

        return $company->archive_reason === $reason;
    }
}
