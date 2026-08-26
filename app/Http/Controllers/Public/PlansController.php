<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\BillingCycle;
use App\Enums\CompanyPackageSku;
use App\Enums\PackageModality;
use App\Http\Controllers\Controller;
use App\Models\PricingSettings;
use App\Services\Pricing\PriceCalculator;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PlansController extends Controller
{
    public function __construct(
        private readonly PriceCalculator $priceCalculator,
    ) {}

    public function index(Request $request): View
    {
        $settings = PricingSettings::current();
        $cycle = BillingCycle::tryFrom((string) $request->query('cycle', 'monthly'))
            ?? BillingCycle::Monthly;
        $modality = PackageModality::tryFrom((string) $request->query('modality', 'manual'))
            ?? PackageModality::Manual;

        $matrix = $this->priceCalculator->matrix($cycle, $settings);
        $supervisionMatrix = $this->priceCalculator->matrixSupervision($cycle, $settings);
        $annualDiscount = (float) config('tenancy.pricing.annual_discount', 0.17);

        $minMonthly = $this->priceCalculator->quote(PackageModality::Manual, 1, BillingCycle::Monthly, $settings);
        $comboSku = CompanyPackageSku::tryFrom((string) $request->query('sku'))
            ?? CompanyPackageSku::fromParts(1, $modality);

        return view('modules.public.plans.index', compact(
            'settings',
            'matrix',
            'supervisionMatrix',
            'cycle',
            'modality',
            'annualDiscount',
            'minMonthly',
            'comboSku',
        ));
    }
}
