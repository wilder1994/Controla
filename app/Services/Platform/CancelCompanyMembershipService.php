<?php

declare(strict_types=1);

namespace App\Services\Platform;

use App\Enums\EvidenceEventType;
use App\Enums\SubscriptionStatus;
use App\Models\SecurityCompany;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class CancelCompanyMembershipService
{
    public function __construct(
        private readonly RecordLifecycleEvidenceService $evidenceService,
    ) {}

    public function execute(SecurityCompany $company, User $actor, string $reason): SecurityCompany
    {
        $reason = trim($reason);
        abort_unless($reason !== '', 422, 'Debe indicar el motivo de cancelación.');

        if ($company->archived_at !== null) {
            throw new \InvalidArgumentException('La empresa ya está archivada.');
        }

        if ($company->hasPendingCancellation()) {
            throw new \InvalidArgumentException('La membresía ya tiene cancelación programada al fin del periodo.');
        }

        return DB::transaction(function () use ($company, $actor, $reason) {
            $now = CarbonImmutable::now();

            $company->update([
                'cancel_at_period_end' => true,
                'cancelled_at' => $now,
                'cancellation_reason' => $reason,
                // Sigue operativa hasta package_ends_at; marca visible en UI.
                'subscription_status' => $company->isUpToDate()
                    ? SubscriptionStatus::Cancelled
                    : SubscriptionStatus::Suspended,
            ]);

            $this->evidenceService->record(
                EvidenceEventType::MembershipCancelled,
                'Cancelación de membresía solicitada',
                [
                    'reason' => $reason,
                    'cancelled_by_user_id' => $actor->id,
                    'package_ends_at' => $company->package_ends_at?->toIso8601String(),
                    'access_until' => $company->package_ends_at?->toIso8601String(),
                ],
                $company->id,
            );

            return $company->fresh();
        });
    }
}
