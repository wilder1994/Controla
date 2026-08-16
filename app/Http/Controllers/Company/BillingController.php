<?php

declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Enums\BillingCycle;
use App\Enums\CompanyPackageSku;
use App\Enums\ManualPaymentIntent;
use App\Enums\PaymentStatus;
use App\Enums\PlatformDocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\CancelMembershipRequest;
use App\Http\Requests\Company\SchedulePackageChangeRequest;
use App\Models\CommercialPayment;
use App\Models\PlatformDocument;
use App\Models\SecurityCompany;
use App\Services\Platform\CancelCompanyMembershipService;
use App\Services\Platform\ScheduleCompanyPackageChangeService;
use App\Services\Platform\UndoCompanyMembershipCancellationService;
use App\Services\Pricing\PriceCalculator;
use App\Support\Platform\ActingCompanyResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class BillingController extends Controller
{
    public function __construct(
        private readonly CancelCompanyMembershipService $cancelCompanyMembershipService,
        private readonly UndoCompanyMembershipCancellationService $undoCompanyMembershipCancellationService,
        private readonly ScheduleCompanyPackageChangeService $scheduleCompanyPackageChangeService,
        private readonly PriceCalculator $priceCalculator,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('company.dashboard'), 403);

        $company = $this->resolveCompany($request);
        $company->loadMissing('subscriptionAcceptances');

        $payments = CommercialPayment::query()
            ->where('security_company_id', $company->id)
            ->latest('created_at')
            ->get();

        $invoices = PlatformDocument::query()
            ->where('security_company_id', $company->id)
            ->where('type', PlatformDocumentType::Invoice)
            ->latest('issued_at')
            ->latest('id')
            ->get();

        $invoiceByPaymentId = $invoices
            ->filter(fn (PlatformDocument $doc) => ! empty($doc->metadata['payment_id']))
            ->keyBy(fn (PlatformDocument $doc) => (int) $doc->metadata['payment_id']);

        $timeline = $company->lifecycleEvidenceEvents()
            ->latest('occurred_at')
            ->limit(50)
            ->get();

        $pendingPayment = $payments
            ->first(fn (CommercialPayment $p) => $p->status === PaymentStatus::Pending && $p->gateway_driver === 'local');

        $sku = $company->package_sku ?? CompanyPackageSku::Pack10Manual;
        $quote = $this->priceCalculator->quote($sku->modality(), $sku->size(), BillingCycle::Monthly);
        $quoteAnnual = $this->priceCalculator->quote($sku->modality(), $sku->size(), BillingCycle::Annual);

        $isUpToDate = $company->isUpToDate();
        $defaultCheckoutIntent = match (true) {
            $company->needsPaidReactivation() => ManualPaymentIntent::Reactivate->value,
            $isUpToDate => ManualPaymentIntent::Anticipate->value,
            default => ManualPaymentIntent::Renew->value,
        };

        return view('modules.company.billing.index', [
            'company' => $company,
            'hasAcceptance' => $company->hasCompletedAcceptance(),
            'payments' => $payments,
            'invoices' => $invoices,
            'invoiceByPaymentId' => $invoiceByPaymentId,
            'timeline' => $timeline,
            'completedCount' => $payments->where('status', PaymentStatus::Completed)->count(),
            'pendingCount' => $payments->where('status', PaymentStatus::Pending)->count(),
            'invoicesCount' => $invoices->count(),
            'pendingPayment' => $pendingPayment,
            'isUpToDate' => $isUpToDate,
            'quote' => $quote,
            'quoteAnnual' => $quoteAnnual,
            'packageOptions' => CompanyPackageSku::options(),
            'cycleOptions' => BillingCycle::options(),
            'defaultCheckoutIntent' => $defaultCheckoutIntent,
            'gatewayDriver' => config('billing.gateway.driver'),
        ]);
    }

    public function cancelMembership(CancelMembershipRequest $request): RedirectResponse
    {
        $company = $this->resolveCompany($request);

        try {
            $this->cancelCompanyMembershipService->execute(
                $company,
                $request->user(),
                $request->validated('reason'),
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('company.billing.index')
                ->with('warning', $e->getMessage());
        }

        return redirect()
            ->route('company.billing.index')
            ->with('success', 'Membresía cancelada. El acceso continúa hasta el fin del periodo contratado.');
    }

    public function undoCancellation(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('company.dashboard'), 403);

        $company = $this->resolveCompany($request);

        try {
            $this->undoCompanyMembershipCancellationService->execute($company, $request->user());
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('company.billing.index')
                ->with('warning', $e->getMessage());
        }

        $ends = $company->fresh()->package_ends_at?->format('d/m/Y');

        return redirect()
            ->route('company.billing.index')
            ->with(
                'success',
                $ends
                    ? "Cancelación deshecha. La membresía sigue activa hasta el {$ends}."
                    : 'Cancelación deshecha. La membresía quedó activa.',
            );
    }

    public function schedulePackageChange(SchedulePackageChangeRequest $request): RedirectResponse
    {
        $company = $this->resolveCompany($request);

        try {
            $payment = $this->scheduleCompanyPackageChangeService->scheduleWithLocalCheckout(
                $company,
                $request->user(),
                CompanyPackageSku::from($request->validated('package_sku')),
                BillingCycle::from($request->validated('billing_cycle')),
            );
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return redirect()
                ->route('company.billing.index')
                ->withInput()
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
