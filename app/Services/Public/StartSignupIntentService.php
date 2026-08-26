<?php

declare(strict_types=1);

namespace App\Services\Public;

use App\Domain\Pricing\Data\AccessSeatSplit;
use App\Enums\BillingCycle;
use App\Enums\CompanyPackageSku;
use App\Enums\SignupIntentStatus;
use App\Enums\SupervisionPackageSku;
use App\Models\CommercialSignupIntent;
use App\Services\Pricing\PriceCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class StartSignupIntentService
{
    public function __construct(
        private readonly PriceCalculator $priceCalculator,
    ) {}

    public function execute(
        CompanyPackageSku $sku,
        BillingCycle $cycle,
        ?SupervisionPackageSku $supervisionSku = null,
        ?AccessSeatSplit $seats = null,
    ): CommercialSignupIntent {
        $seats ??= AccessSeatSplit::fromSku($sku);
        if ($seats->size() !== $sku->size()) {
            throw new InvalidArgumentException('La mezcla de asientos no coincide con el cupo.');
        }

        if ($supervisionSku !== null && $seats->size() < 5) {
            throw new InvalidArgumentException('El paquete de 1 cliente no incluye Supervisión.');
        }

        $quote = $this->priceCalculator->quoteAccess($seats, $cycle);
        $amount = $cycle === BillingCycle::Annual ? $quote->priceAnnual : $quote->priceMonthly;

        if ($supervisionSku !== null) {
            $sup = $this->priceCalculator->quoteSupervisionForAccess($supervisionSku, $seats->size(), $cycle);
            $amount += $cycle === BillingCycle::Annual ? $sup->priceAnnual : $sup->priceMonthly;
        }

        $now = CarbonImmutable::now();

        return CommercialSignupIntent::query()->create([
            'token' => (string) Str::uuid(),
            'status' => SignupIntentStatus::Draft,
            'package_sku' => $seats->sku(),
            'package_manual_seats' => $seats->manual,
            'package_hardware_seats' => $seats->hardware,
            'supervision_package_sku' => $supervisionSku,
            'billing_cycle' => $cycle,
            'amount' => $amount,
            'currency' => $quote->currency,
            'expires_at' => $now->addHours((int) config('billing.signup_intent_ttl_hours', 24)),
        ]);
    }
}
