<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\BillingCycle;
use App\Enums\CommercialProduct;
use App\Http\Controllers\Controller;
use App\Support\Catalog\CatalogPricer;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PlansController extends Controller
{
    public function index(Request $request): View
    {
        $cycle = BillingCycle::tryFrom((string) $request->query('cycle', 'monthly'))
            ?? BillingCycle::Monthly;
        $pricer = CatalogPricer::make();
        $accessMatrix = $pricer->matrix(CommercialProduct::Access, $cycle);
        $supervisionMatrix = $pricer->matrix(CommercialProduct::Supervision, $cycle);
        $indexingMatrix = $pricer->matrix(CommercialProduct::Indexing, $cycle);
        $indexingAddonMatrix = $pricer->matrix(CommercialProduct::Indexing, $cycle, true);
        $observatoryMatrix = $pricer->observatoryMatrix($cycle);
        $annualDiscount = (float) config('tenancy.pricing.annual_discount', 0.17);
        $minMonthly = $accessMatrix[0]['price_monthly'] ?? 0;
        $signupSku = [
            'bronce' => 'pack_5_manual',
            'plata' => 'pack_10_manual',
            'oro' => 'pack_50_manual',
            'platino' => 'pack_100_manual',
        ];

        return view('modules.public.plans.index', compact(
            'cycle',
            'accessMatrix',
            'supervisionMatrix',
            'indexingMatrix',
            'indexingAddonMatrix',
            'observatoryMatrix',
            'annualDiscount',
            'minMonthly',
            'signupSku',
        ));
    }
}
