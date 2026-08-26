<?php

declare(strict_types=1);

namespace App\Services\Public;

use App\Enums\BillingCycle;
use App\Enums\CompanyPackageSku;
use App\Enums\SignupIntentStatus;
use App\Enums\SupervisionPackageSku;
use App\Models\CommercialSignupIntent;
use App\Services\Pricing\PriceCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

final class StartSignupIntentService
{
    public function __construct(
        private readonly PriceCalculator $priceCalculator,
    ) {}

    public function execute(
        CompanyPackageSku $sku,
        BillingCycle $cycle,
        ?SupervisionPackageSku $supervisionSku = null,
    ): CommercialSignupIntent {
        $quote = $this->priceCalculator->quote($sku->modality(), $sku->size(), $cycle);
        $amount = $cycle === BillingCycle::Annual ? $quote->priceAnnual : $quote->priceMonthly;

        if ($supervisionSku !== null) {
            $pro = $this->priceCalculator->quoteSupervision($supervisionSku->size(), $cycle);
            $amount += $cycle === BillingCycle::Annual ? $pro->priceAnnual : $pro->priceMonthly;
        }

        $now = CarbonImmutable::now();

        return CommercialSignupIntent::query()->create([
            'token' => (string) Str::uuid(),
            'status' => SignupIntentStatus::Draft,
            'package_sku' => $sku,
            'supervision_package_sku' => $supervisionSku,
            'billing_cycle' => $cycle,
            'amount' => $amount,
            'currency' => $quote->currency,
            'expires_at' => $now->addHours((int) config('billing.signup_intent_ttl_hours', 24)),
        ]);
    }
}
