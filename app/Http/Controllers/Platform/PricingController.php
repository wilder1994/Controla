<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Enums\BillingCycle;
use App\Enums\CommercialProduct;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\UpdatePlatformPricingRequest;
use App\Services\Pricing\UpdatePlatformPricingService;
use App\Support\Catalog\CatalogPricer;
use App\Support\Catalog\CatalogSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PricingController extends Controller
{
    public function __construct(
        private readonly UpdatePlatformPricingService $updatePlatformPricingService,
    ) {}

    public function edit(Request $request): View
    {
        abort_unless(auth()->user()?->can('platform.companies.view'), 403);

        $cycle = BillingCycle::tryFrom((string) $request->query('cycle', 'monthly'))
            ?? BillingCycle::Monthly;
        $pricer = CatalogPricer::make();
        $catalog = $pricer->catalog()->toForm();
        $accessMatrix = $pricer->matrix(CommercialProduct::Access, $cycle);
        $supervisionMatrix = $pricer->matrix(CommercialProduct::Supervision, $cycle);
        $indexingMatrix = $pricer->matrix(CommercialProduct::Indexing, $cycle);
        $indexingAddonMatrix = $pricer->matrix(CommercialProduct::Indexing, $cycle, true);
        $observatoryMatrix = $pricer->observatoryMatrix($cycle);
        $annualDiscount = (float) config('tenancy.pricing.annual_discount', 0.17);
        $moduleOptions = CatalogSettings::defaults()['module_options'] ?? [];

        return view('modules.admin.pricing.edit', compact(
            'cycle',
            'catalog',
            'accessMatrix',
            'supervisionMatrix',
            'indexingMatrix',
            'indexingAddonMatrix',
            'observatoryMatrix',
            'annualDiscount',
            'moduleOptions',
        ));
    }

    public function update(UpdatePlatformPricingRequest $request): RedirectResponse
    {
        $this->updatePlatformPricingService->execute($request->validated(), $request->user());

        return redirect()
            ->route('admin.pricing.edit')
            ->with('success', 'Catálogo actualizado. Las cuatro tablas se recalcularon.');
    }
}
