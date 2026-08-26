<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Enums\BillingCycle;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\UpdatePlatformPricingRequest;
use App\Models\PricingSettings;
use App\Services\Pricing\PriceCalculator;
use App\Services\Pricing\UpdatePlatformPricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PricingController extends Controller
{
    public function __construct(
        private readonly PriceCalculator $priceCalculator,
        private readonly UpdatePlatformPricingService $updatePlatformPricingService,
    ) {}

    public function edit(Request $request): View
    {
        abort_unless(auth()->user()?->can('platform.companies.view'), 403);

        $settings = PricingSettings::current();
        $cycle = BillingCycle::tryFrom((string) $request->query('cycle', 'monthly'))
            ?? BillingCycle::Monthly;
        $matrix = $this->priceCalculator->matrix($cycle, $settings);
        $supervisionMatrix = $this->priceCalculator->matrixSupervision($cycle, $settings);
        $annualDiscount = (float) config('tenancy.pricing.annual_discount', 0.17);

        return view('modules.admin.pricing.edit', compact(
            'settings',
            'matrix',
            'supervisionMatrix',
            'cycle',
            'annualDiscount',
        ));
    }

    public function update(UpdatePlatformPricingRequest $request): RedirectResponse
    {
        $this->updatePlatformPricingService->execute(
            unitManual: (float) $request->validated('unit_price_manual'),
            unitHardware: (float) $request->validated('unit_price_hardware'),
            unitSupervision: (float) $request->validated('unit_price_supervision'),
            actor: $request->user(),
        );

        return redirect()
            ->route('admin.pricing.edit')
            ->with('success', 'Precios base actualizados. La tabla se recalculó automáticamente.');
    }
}
