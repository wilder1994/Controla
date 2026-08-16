<?php

declare(strict_types=1);

namespace App\Services\Platform;

use App\Enums\EvidenceEventType;
use App\Enums\SubscriptionStatus;
use App\Models\SecurityCompany;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UndoCompanyMembershipCancellationService
{
    public function __construct(
        private readonly RecordLifecycleEvidenceService $evidenceService,
    ) {}

    public function execute(SecurityCompany $company, User $actor): SecurityCompany
    {
        if (! $company->hasPendingCancellation()) {
            throw new \InvalidArgumentException('No hay una cancelación pendiente para deshacer.');
        }

        if (! $company->isUpToDate()) {
            throw new \InvalidArgumentException(
                'El periodo contratado ya venció. Debe registrar un pago para reactivar la membresía.',
            );
        }

        return DB::transaction(function () use ($company, $actor) {
            $company->update([
                'cancel_at_period_end' => false,
                'cancelled_at' => null,
                'cancellation_reason' => null,
                'subscription_status' => SubscriptionStatus::Active,
                'is_active' => true,
            ]);

            $this->evidenceService->record(
                EvidenceEventType::MembershipReactivated,
                'Cancelación deshecha — membresía activa',
                [
                    'undone_by_user_id' => $actor->id,
                    'package_ends_at' => $company->package_ends_at?->toIso8601String(),
                    'without_payment' => true,
                ],
                $company->id,
            );

            return $company->fresh();
        });
    }
}
