<?php

declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Enums\ManualPaymentIntent;
use App\Http\Controllers\Controller;
use App\Models\SecurityCompany;
use App\Services\Platform\RegisterCommercialPaymentService;
use App\Support\Platform\ActingCompanyResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class BillingCheckoutController extends Controller
{
    public function __construct(
        private readonly RegisterCommercialPaymentService $paymentService,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('company.dashboard'), 403);

        $company = $this->resolveCompany($request);

        $validated = $request->validate([
            'intent' => ['nullable', Rule::enum(ManualPaymentIntent::class)],
        ]);

        $intent = isset($validated['intent'])
            ? ManualPaymentIntent::from($validated['intent'])
            : null;

        if ($intent === ManualPaymentIntent::PlanChange) {
            return redirect()
                ->route('company.billing.index')
                ->with('warning', 'Para cambiar de plan use «Programar cambio».');
        }

        try {
            $payment = $this->paymentService->initiateLocalCheckout(
                $company,
                $request->user(),
                $intent,
            );
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return redirect()
                ->route('company.billing.index')
                ->with('warning', $e->getMessage());
        }

        return redirect()->route('billing.checkout.show', $payment);
    }

    private function resolveCompany(Request $request): SecurityCompany
    {
        $companyId = app(ActingCompanyResolver::class)->requireId($request->user());

        return SecurityCompany::query()->findOrFail($companyId);
    }
}
