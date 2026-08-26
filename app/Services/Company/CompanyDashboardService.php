<?php

declare(strict_types=1);

namespace App\Services\Company;

use App\Enums\BillingCycle;
use App\Enums\CompanyPackageSku;
use App\Enums\PackageModality;
use App\Models\SecurityCompany;
use App\Repositories\ClientRepository;
use App\Services\Pricing\PriceCalculator;

final class CompanyDashboardService
{
    public function __construct(
        private readonly ClientRepository $clientRepository,
        private readonly CompanyDashboardAnalytics $analytics,
        private readonly PriceCalculator $priceCalculator,
        private readonly BuildFieldSupervisionStripService $fieldSupervisionStrip,
    ) {}

    /** @return array<string, mixed> */
    public function build(SecurityCompany $company): array
    {
        $companyId = (int) $company->id;
        $metrics = $this->clientRepository->metricsForCompany($companyId);
        $ops = $this->analytics->build($company);

        $modality = PackageModality::tryFrom((string) $metrics['package_modality'])
            ?? PackageModality::Manual;
        $currentSize = (int) $metrics['max_clients'];
        $upgradeSizes = collect(config('tenancy.package_sizes', [1, 5, 10, 50, 100]))
            ->map(static fn ($size) => (int) $size)
            ->filter(static fn (int $size) => $size > $currentSize)
            ->values()
            ->all();

        $upgradeQuotes = [];
        foreach ($upgradeSizes as $index => $size) {
            $monthly = $this->priceCalculator->quote($modality, $size, BillingCycle::Monthly);
            $annual = $this->priceCalculator->quote($modality, $size, BillingCycle::Annual);
            $upgradeQuotes[] = [
                'size' => $size,
                'label' => CompanyPackageSku::fromParts($size, $modality)->label(),
                'monthly' => $monthly,
                'annual' => $annual,
                'recommended' => $index === 0,
            ];
        }

        $annualForCurrent = $this->priceCalculator->quote($modality, max(1, $currentSize), BillingCycle::Annual);

        return [
            'company' => $company,
            'metrics' => $metrics,
            'ops' => $ops,
            'upgradeQuotes' => $upgradeQuotes,
            'annualForCurrent' => $annualForCurrent,
            'fieldSupervision' => $this->fieldSupervisionStrip->forToday($company),
        ];
    }
}
