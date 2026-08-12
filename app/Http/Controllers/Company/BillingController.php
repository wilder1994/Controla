<?php

declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CommercialPayment;
use App\Models\SecurityCompany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class BillingController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('company.dashboard'), 403);

        $company = $this->resolveCompany($request);
        $company->loadMissing('subscriptionAcceptances');

        $latestPayment = CommercialPayment::query()
            ->where('security_company_id', $company->id)
            ->latest('created_at')
            ->first();

        $pendingPayment = CommercialPayment::query()
            ->where('security_company_id', $company->id)
            ->where('status', 'pending')
            ->where('gateway_driver', 'local')
            ->latest('created_at')
            ->first();

        return view('modules.company.billing.index', [
            'company' => $company,
            'hasAcceptance' => $company->hasCompletedAcceptance(),
            'latestPayment' => $latestPayment,
            'pendingPayment' => $pendingPayment,
            'gatewayDriver' => config('billing.gateway.driver'),
        ]);
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
