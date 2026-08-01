<?php

declare(strict_types=1);

namespace App\Services\Platform;

use App\Enums\SubscriptionStatus;
use App\Models\SecurityCompany;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class SuspendCompanyService
{
    public function execute(SecurityCompany $company, ?CarbonImmutable $at = null): SecurityCompany
    {
        $at ??= CarbonImmutable::now();

        return DB::transaction(function () use ($company, $at) {
            $company->update([
                'is_active' => false,
                'subscription_status' => SubscriptionStatus::Suspended,
                'suspended_at' => $at,
            ]);

            return $company->fresh();
        });
    }
}
