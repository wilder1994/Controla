<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\BillingCycle;
use App\Enums\PackageModality;
use App\Http\Controllers\Controller;
use App\Models\PricingSettings;
use App\Services\Pricing\PriceCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class WelcomeController extends Controller
{
    public function __construct(
        private readonly PriceCalculator $priceCalculator,
    ) {}

    public function __invoke(Request $request): View|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('home');
        }

        $settings = PricingSettings::current();
        $minMonthly = $this->priceCalculator->quote(PackageModality::Manual, 1, BillingCycle::Monthly, $settings);
        $annualDiscount = (float) config('tenancy.pricing.annual_discount', 0.17);

        return view('welcome', compact('minMonthly', 'annualDiscount'));
    }
}
