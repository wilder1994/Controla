<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Enums\BillingCycle;
use App\Enums\SupervisionPackageSku;
use App\Models\CommercialPayment;
use App\Models\SecurityCompany;
use App\Models\User;
use App\Services\Platform\ScheduleCompanyPackageChangeService;

final class PurchaseCompanySupervisionPackageService
{
    public function __construct(
        private readonly ScheduleCompanyPackageChangeService $scheduleCompanyPackageChangeService,
    ) {}

    /**
     * Programar Supervisión al corte. Quitar no cobra. Alta/cambio abre checkout.
     */
    public function execute(
        SecurityCompany $company,
        User $actor,
        ?SupervisionPackageSku $sku,
    ): CommercialPayment|SecurityCompany {
        if ($sku === $company->supervision_package_sku) {
            throw new \InvalidArgumentException(
                $sku === null
                    ? 'La empresa ya está sin Supervisión.'
                    : 'El cupo de Supervisión seleccionado es el mismo que el actual.',
            );
        }

        $accessSize = (int) ($company->package_size ?: 0);
        if ($sku !== null && $accessSize < 5) {
            throw new \InvalidArgumentException('El paquete de 1 cliente de Accesos no puede contratar Supervisión.');
        }

        if ($sku === null) {
            return $this->scheduleCompanyPackageChangeService->scheduleSupervisionRemoval($company);
        }

        $skuEnum = $company->package_sku;
        if ($skuEnum === null) {
            throw new \InvalidArgumentException('La empresa no tiene paquete de Accesos.');
        }

        $seats = $company->accessSeats();

        return $this->scheduleCompanyPackageChangeService->scheduleWithLocalCheckout(
            $company,
            $actor,
            $skuEnum,
            $company->billing_cycle ?? BillingCycle::Monthly,
            $seats,
            true,
            $sku,
        );
    }
}
