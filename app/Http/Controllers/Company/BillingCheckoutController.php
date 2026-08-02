<?php

declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\SecurityCompany;
use App\Services\Platform\RegisterCommercialPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class BillingCheckoutController extends Controller
{
    public function __construct(
        private readonly RegisterCommercialPaymentService $paymentService,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('company.dashboard'), 403);

        $company = $this->resolveCompany($request);

        try {
            $payment = $this->paymentService->initiateLocalCheckout($company, $request->user());
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return redirect()
                ->route('company.billing.index')
                ->with('warning', $e->getMessage());
        }

        return redirect()->route('billing.checkout.show', $payment);
    }

    private function resolveCompany(Request $request): SecurityCompany
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $companyId = (int) $user->security_company_id;
        abort_unless($companyId > 0, 403, 'Usuario sin empresa asignada.');

        return SecurityCompany::query()->findOrFail($companyId);
    }
}
