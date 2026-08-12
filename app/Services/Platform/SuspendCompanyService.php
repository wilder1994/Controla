<?php

declare(strict_types=1);

namespace App\Services\Platform;

use App\Enums\SubscriptionStatus;
use App\Models\SecurityCompany;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class SuspendCompanyService
{
    public function __construct(
        private readonly RecordLifecycleEvidenceService $evidenceService,
    ) {}

    public function execute(SecurityCompany $company, ?CarbonImmutable $at = null): SecurityCompany
    {
        $at ??= CarbonImmutable::now();

        return DB::transaction(function () use ($company, $at) {
            $company->update([
                'is_active' => false,
                'subscription_status' => SubscriptionStatus::Suspended,
                'suspended_at' => $at,
            ]);

            $this->evidenceService->record(
                \App\Enums\EvidenceEventType::CompanySuspended,
                'Acta de suspensión por falta de pago',
                [
                    'suspended_at' => $at->toIso8601String(),
                    'subscription_status' => SubscriptionStatus::Suspended->value,
                ],
                $company->id,
                null,
                $at,
            );

            return $company->fresh();
        });
    }
}
