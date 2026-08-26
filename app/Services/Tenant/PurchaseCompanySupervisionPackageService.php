<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Enums\BillingCycle;
use App\Enums\ManualPaymentIntent;
use App\Enums\PaymentStatus;
use App\Enums\SupervisionPackageSku;
use App\Models\CommercialPayment;
use App\Models\SecurityCompany;
use App\Models\User;
use App\Services\Platform\RegisterCommercialPaymentService;
use App\Services\Pricing\PriceCalculator;

final class PurchaseCompanySupervisionPackageService
{
    public function __construct(
        private readonly AssignCompanySupervisionPackageService $assignCompanySupervisionPackageService,
        private readonly RegisterCommercialPaymentService $paymentService,
        private readonly PriceCalculator $priceCalculator,
    ) {}

    /**
     * Quita Pro al instante. Alta o cambio de cupo abre checkout local.
     */
    public function execute(
        SecurityCompany $company,
        User $actor,
        ?SupervisionPackageSku $sku,
    ): CommercialPayment|SecurityCompany {
        if ($sku === $company->supervision_package_sku) {
            throw new \InvalidArgumentException(
                $sku === null
                    ? 'La empresa ya está sin Supervisión Pro.'
                    : 'El cupo Pro seleccionado es el mismo que el actual.',
            );
        }

        if ($sku === null) {
            return $this->assignCompanySupervisionPackageService->execute($company, null);
        }

        if (! $company->isUpToDate()) {
            throw new \InvalidArgumentException(
                'Sin periodo Accesos vigente no se puede contratar Supervisión Pro. Renueve o reactive primero.',
            );
        }

        $pending = CommercialPayment::query()
            ->where('security_company_id', $company->id)
            ->where('status', PaymentStatus::Pending)
            ->where('gateway_driver', 'local')
            ->exists();

        if ($pending) {
            throw new \InvalidArgumentException('Hay un pago online pendiente. Confírmelo o rechácelo antes de cambiar Pro.');
        }

        $cycle = $company->billing_cycle ?? BillingCycle::Monthly;
        $quote = $this->priceCalculator->quoteSupervision($sku->size(), $cycle);
        $amount = $cycle === BillingCycle::Annual
            ? (float) $quote->priceAnnual
            : (float) $quote->priceMonthly;

        return $this->paymentService->initiateLocalCheckout(
            $company,
            $actor,
            ManualPaymentIntent::SupervisionChange,
            $amount,
            $cycle,
            [
                'kind' => 'supervision_package_change',
                'from_sku' => $company->supervision_package_sku?->value,
                'to_sku' => $sku->value,
            ],
        );
    }
}
