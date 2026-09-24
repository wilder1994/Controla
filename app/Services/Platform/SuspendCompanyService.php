<?php

declare(strict_types=1);

namespace App\Services\Platform;

use App\Enums\EvidenceEventType;
use App\Enums\SubscriptionStatus;
use App\Models\SecurityCompany;
use App\Support\Company\CompanyServiceAccess;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class SuspendCompanyService
{
    public function __construct(
        private readonly RecordLifecycleEvidenceService $evidenceService,
    ) {}

    public function execute(
        SecurityCompany $company,
        ?CarbonImmutable $at = null,
        string $title = 'Acta de suspensión por falta de pago',
    ): SecurityCompany {
        $at ??= CarbonImmutable::now();

        return DB::transaction(function () use ($company, $at, $title) {
            $company->update([
                'is_active' => false,
                'subscription_status' => SubscriptionStatus::Suspended,
                'suspended_at' => $at,
            ]);

            CompanyServiceAccess::revokeOperationalTokens((int) $company->id);

            $this->evidenceService->record(
                EvidenceEventType::CompanySuspended,
                $title,
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
