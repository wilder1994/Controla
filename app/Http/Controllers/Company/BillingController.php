<?php

declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Domain\Pricing\Data\AccessSeatSplit;
use App\Enums\BillingCycle;
use App\Enums\CompanyPackageSku;
use App\Enums\ManualPaymentIntent;
use App\Enums\PaymentStatus;
use App\Enums\PlatformDocumentType;
use App\Enums\SupervisionPackageSku;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\CancelMembershipRequest;
use App\Http\Requests\Company\SchedulePackageChangeRequest;
use App\Http\Requests\Company\UpdateSupervisionPackageRequest;
use App\Models\CommercialPayment;
use App\Models\PlatformDocument;
use App\Models\SecurityCompany;
use App\Services\Platform\CancelCompanyMembershipService;
use App\Services\Platform\ScheduleCompanyPackageChangeService;
use App\Services\Platform\UndoCompanyMembershipCancellationService;
use App\Services\Pricing\PriceCalculator;
use App\Services\Tenant\PurchaseCompanySupervisionPackageService;
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
        private readonly PurchaseCompanySupervisionPackageService $purchaseCompanySupervisionPackageService,
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

        $seats = $company->accessSeats();
        $quote = $this->priceCalculator->quoteAccess($seats, BillingCycle::Monthly);
        $quoteAnnual = $this->priceCalculator->quoteAccess($seats, BillingCycle::Annual);

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
            'supervisionOptions' => SupervisionPackageSku::selectableOptions((int) ($company->package_size ?: 0)),
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
            $sku = CompanyPackageSku::from($request->validated('package_sku'));
            $cycle = BillingCycle::from($request->validated('billing_cycle'));
            $seats = AccessSeatSplit::resolve(
                $sku,
                isset($request->validated()['manual_seats']) ? (int) $request->validated('manual_seats') : null,
                isset($request->validated()['hardware_seats']) ? (int) $request->validated('hardware_seats') : null,
            );
            $supValue = $request->validated('supervision_package_sku') ?? null;
            $includeSup = $request->has('supervision_package_sku');
            $sup = ($includeSup && $supValue) ? SupervisionPackageSku::from($supValue) : null;

            $payment = $this->scheduleCompanyPackageChangeService->scheduleWithLocalCheckout(
                $company,
                $request->user(),
                $sku,
                $cycle,
                $seats,
                $includeSup,
                $sup,
            );
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return redirect()
                ->route('company.billing.index')
                ->withInput()
                ->with('warning', $e->getMessage());
        }

        return redirect()->route('billing.checkout.show', $payment);
    }

    public function updateSupervisionPackage(UpdateSupervisionPackageRequest $request): RedirectResponse
    {
        $company = $this->resolveCompany($request);
        $skuValue = $request->validated('supervision_package_sku');
        $sku = $skuValue ? SupervisionPackageSku::from($skuValue) : null;

        try {
            $result = $this->purchaseCompanySupervisionPackageService->execute(
                $company,
                $request->user(),
                $sku,
            );
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return redirect()
                ->route('company.billing.index')
                ->withInput()
                ->with('warning', $e->getMessage());
        }

        if ($result instanceof CommercialPayment) {
            return redirect()->route('billing.checkout.show', $result);
        }

        $label = $sku?->label() ?? 'sin Supervisión';

        return redirect()
            ->route('company.billing.index')
            ->with('success', "Supervisión programada al corte: {$label}.");
    }

    private function resolveCompany(Request $request): SecurityCompany
    {
        $companyId = app(ActingCompanyResolver::class)->requireId($request->user());

        return SecurityCompany::query()->findOrFail($companyId);
    }
}
