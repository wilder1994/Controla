<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Domain\Geo\GeoAddressData;
use App\Enums\BillingCycle;
use App\Enums\ClientLifecycle;
use App\Enums\ManualPaymentIntent;
use App\Enums\PaymentStatus;
use App\Enums\PlatformDocumentType;
use App\Enums\SupervisionPackageSku;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\CancelCompanyMembershipRequest;
use App\Http\Requests\Platform\SchedulePackageChangeRequest;
use App\Http\Requests\Platform\StoreCompanyRequest;
use App\Http\Requests\Platform\StoreManualPaymentRequest;
use App\Http\Requests\Platform\UpdateCompanyProfileRequest;
use App\Http\Requests\Platform\UpdateCompanySupervisionPackageRequest;
use App\Models\CommercialPayment;
use App\Models\PlatformDocument;
use App\Models\SecurityCompany;
use App\Models\User;
use App\Repositories\SecurityCompanyRepository;
use App\Services\Platform\CancelCompanyMembershipService;
use App\Services\Platform\EnterCompanyAsSupportService;
use App\Services\Platform\RegisterCommercialPaymentService;
use App\Services\Platform\ScheduleCompanyPackageChangeService;
use App\Services\Platform\UndoCompanyMembershipCancellationService;
use App\Services\Pricing\PriceCalculator;
use App\Services\Tenant\AssignCompanyPackageService;
use App\Services\Tenant\AssignCompanySupervisionPackageService;
use App\Services\Tenant\CreateCompanyService;
use App\Services\Tenant\UpdateCompanyProfileService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class CompanyController extends Controller
{
    public function __construct(
        private readonly SecurityCompanyRepository $securityCompanyRepository,
        private readonly AssignCompanyPackageService $assignCompanyPackageService,
        private readonly AssignCompanySupervisionPackageService $assignCompanySupervisionPackageService,
        private readonly PriceCalculator $priceCalculator,
        private readonly UpdateCompanyProfileService $updateCompanyProfileService,
        private readonly CreateCompanyService $createCompanyService,
        private readonly EnterCompanyAsSupportService $enterCompanyAsSupportService,
        private readonly RegisterCommercialPaymentService $paymentService,
        private readonly CancelCompanyMembershipService $cancelCompanyMembershipService,
        private readonly UndoCompanyMembershipCancellationService $undoCompanyMembershipCancellationService,
        private readonly ScheduleCompanyPackageChangeService $scheduleCompanyPackageChangeService,
    ) {}

    public function index(): View
    {
        abort_unless(auth()->user()?->can('platform.companies.view'), 403);

        $companies = $this->securityCompanyRepository->paginate();
        $kpis = $this->securityCompanyRepository->companiesIndexKpis();

        return view('modules.admin.companies.index', compact('companies', 'kpis'));
    }

    public function create(): View
    {
        abort_unless(auth()->user()?->can('platform.companies.manage'), 403);

        return view('modules.admin.companies.create');
    }

    public function store(StoreCompanyRequest $request): RedirectResponse
    {
        $company = $this->createCompanyService->execute(
            $request->safe()->except(GeoAddressData::formKeys()),
            GeoAddressData::fromValidated($request->validated()),
        );

        return redirect()
            ->route('admin.companies.show', $company)
            ->with('success', "Empresa «{$company->displayName()}» creada.");
    }

    public function show(Request $request, SecurityCompany $company): View
    {
        abort_unless(auth()->user()?->can('platform.companies.view'), 403);

        $company->loadCount('clients')
            ->loadCount([
                'clients as operational_clients_count' => fn ($q) => $q->where('lifecycle', ClientLifecycle::Active),
            ]);

        $packageOptions = CompanyPackageSku::options();
        $cycleOptions = BillingCycle::options();

        $previewSku = CompanyPackageSku::tryFrom((string) $request->old('package_sku', $company->package_sku?->value ?? 'pack_10_manual'))
            ?? CompanyPackageSku::Pack10Manual;
        $previewCycle = BillingCycle::tryFrom((string) $request->old('billing_cycle', $company->billing_cycle?->value ?? 'monthly'))
            ?? BillingCycle::Monthly;
        $quote = $this->priceCalculator->quote($previewSku->modality(), $previewSku->size(), $previewCycle);
        $quoteAnnual = $this->priceCalculator->quote($previewSku->modality(), $previewSku->size(), BillingCycle::Annual);

        $clients = $company->clients()
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'lifecycle', 'latitude', 'longitude', 'city']);

        $pendingPaymentsCount = CommercialPayment::query()
            ->where('security_company_id', $company->id)
            ->where('status', PaymentStatus::Pending)
            ->count();

        $paidInvoicesCount = CommercialPayment::query()
            ->where('security_company_id', $company->id)
            ->where('status', PaymentStatus::Completed)
            ->count();

        $latestPayment = CommercialPayment::query()
            ->where('security_company_id', $company->id)
            ->latest('created_at')
            ->first();

        $companyAdminEmail = $this->resolveCompanyAdminEmail($company);

        $maxClients = max(1, (int) $company->max_clients);
        $operational = (int) ($company->operational_clients_count ?? 0);
        $quotaPct = (int) min(100, round(($operational / $maxClients) * 100));

        $daysToRenewal = null;
        if ($company->package_ends_at !== null) {
            $daysToRenewal = (int) CarbonImmutable::now()->startOfDay()
                ->diffInDays(CarbonImmutable::parse($company->package_ends_at)->startOfDay(), false);
        }

        $riskLabel = match (true) {
            $pendingPaymentsCount > 0 || ($daysToRenewal !== null && $daysToRenewal < 15) => 'Alto',
            $quotaPct >= 90 || ($daysToRenewal !== null && $daysToRenewal < 45) => 'Medio',
            default => 'Bajo',
        };

        return view('modules.admin.companies.show', [
            'company' => $company,
            'packageOptions' => $packageOptions,
            'cycleOptions' => $cycleOptions,
            'quote' => $quote,
            'quoteAnnual' => $quoteAnnual,
            'portfolioClients' => $clients,
            'paidInvoicesCount' => $paidInvoicesCount,
            'pendingPaymentsCount' => $pendingPaymentsCount,
            'latestPayment' => $latestPayment,
            'companyAdminEmail' => $companyAdminEmail,
            'quotaPct' => $quotaPct,
            'daysToRenewal' => $daysToRenewal,
            'riskLabel' => $riskLabel,
            'hasAcceptance' => $company->hasCompletedAcceptance(),
            'isUpToDate' => $company->isUpToDate(),
            'opsAlertsCount' => 0,
        ]);
    }

    public function historial(SecurityCompany $company): View
    {
        abort_unless(auth()->user()?->can('platform.companies.view'), 403);

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

        $completedCount = $payments->where('status', PaymentStatus::Completed)->count();
        $pendingCount = $payments->where('status', PaymentStatus::Pending)->count();
        $invoicesCount = $invoices->count();

        return view('modules.admin.companies.historial', [
            'company' => $company,
            'payments' => $payments,
            'invoices' => $invoices,
            'invoiceByPaymentId' => $invoiceByPaymentId,
            'timeline' => $timeline,
            'completedCount' => $completedCount,
            'pendingCount' => $pendingCount,
            'invoicesCount' => $invoicesCount,
        ]);
    }

    public function enterAsSupport(SecurityCompany $company): RedirectResponse
    {
        abort_unless(auth()->user()?->can('platform.companies.manage'), 403);

        $this->enterCompanyAsSupportService->enter(auth()->user(), $company);

        return redirect()
            ->route('company.dashboard')
            ->with('success', "Entraste como «{$company->displayName()}».");
    }

    public function exitSupport(): RedirectResponse
    {
        abort_unless(auth()->user()?->can('platform.companies.manage'), 403);

        $companyId = $this->enterCompanyAsSupportService->exit(auth()->user());

        if ($companyId !== null) {
            return redirect()
                ->route('admin.companies.show', $companyId)
                ->with('success', 'Saliste del modo soporte.');
        }

        return redirect()
            ->route('admin.companies.index')
            ->with('success', 'Saliste del modo soporte.');
    }

    public function storeManualPayment(
        StoreManualPaymentRequest $request,
        SecurityCompany $company,
    ): RedirectResponse {
        try {
            $intent = ManualPaymentIntent::from($request->validated('intent'));
            $this->paymentService->executeManual(
                $company,
                $request->user(),
                $request->validated('reference'),
                $request->file('proof'),
                $intent,
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('warning', $e->getMessage());
        }

        return redirect()
            ->route('admin.companies.historial', $company)
            ->with('success', 'Pago manual registrado. Factura demo generada.');
    }

    public function cancelMembership(
        CancelCompanyMembershipRequest $request,
        SecurityCompany $company,
    ): RedirectResponse {
        try {
            $this->cancelCompanyMembershipService->execute(
                $company,
                $request->user(),
                $request->validated('reason'),
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('admin.companies.show', $company)
                ->withInput()
                ->with('warning', $e->getMessage());
        }

        return redirect()
            ->route('admin.companies.show', $company)
            ->with('success', 'Membresía cancelada. El acceso continúa hasta el fin del periodo contratado.');
    }

    public function undoMembershipCancellation(SecurityCompany $company): RedirectResponse
    {
        abort_unless(auth()->user()?->can('platform.companies.manage'), 403);

        try {
            $this->undoCompanyMembershipCancellationService->execute($company, auth()->user());
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('admin.companies.show', $company)
                ->with('warning', $e->getMessage());
        }

        $ends = $company->fresh()->package_ends_at?->format('d/m/Y');

        return redirect()
            ->route('admin.companies.show', $company)
            ->with(
                'success',
                $ends
                    ? "Cancelación deshecha. La membresía sigue activa hasta el {$ends}."
                    : 'Cancelación deshecha. La membresía quedó activa.',
            );
    }

    public function schedulePackageChange(
        SchedulePackageChangeRequest $request,
        SecurityCompany $company,
    ): RedirectResponse {
        try {
            $this->scheduleCompanyPackageChangeService->scheduleWithManualPayment(
                $company,
                $request->user(),
                CompanyPackageSku::from($request->validated('package_sku')),
                BillingCycle::from($request->validated('billing_cycle')),
                $request->validated('reference'),
                $request->file('proof'),
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('admin.companies.show', $company)
                ->withInput()
                ->with('warning', $e->getMessage());
        }

        return redirect()
            ->route('admin.companies.show', $company)
            ->with('success', 'Cambio de plan programado. Se aplicará al finalizar el periodo actual.');
    }

    public function updatePackage(UpdateCompanyPackageRequest $request, SecurityCompany $company): RedirectResponse
    {
        $sku = CompanyPackageSku::from($request->validated('package_sku'));
        $cycle = BillingCycle::from($request->validated('billing_cycle'));
        $this->assignCompanyPackageService->execute($company, $sku, $cycle);

        return redirect()
            ->route('admin.companies.show', $company)
            ->with('success', "Paquete actualizado a «{$sku->label()}» ({$cycle->label()}).");
    }

    public function updateSupervisionPackage(
        UpdateCompanySupervisionPackageRequest $request,
        SecurityCompany $company,
    ): RedirectResponse {
        $skuValue = $request->validated('supervision_package_sku');
        $sku = $skuValue ? SupervisionPackageSku::from($skuValue) : null;
        $this->assignCompanySupervisionPackageService->execute($company, $sku);

        $label = $sku?->label() ?? 'sin Supervisión Pro';

        return redirect()
            ->route('admin.companies.show', $company)
            ->with('success', "Paquete Pro actualizado: {$label}.");
    }

    public function editProfile(SecurityCompany $company): View
    {
        abort_unless(auth()->user()?->can('updateProfile', $company), 403);

        return view('modules.admin.companies.profile', compact('company'));
    }

    public function updateProfile(UpdateCompanyProfileRequest $request, SecurityCompany $company): RedirectResponse
    {
        $this->updateCompanyProfileService->assertTaxIdImmutable($company, $request->input('tax_id'));

        $this->updateCompanyProfileService->execute(
            $company,
            $request->safe()->except(GeoAddressData::formKeys()),
            GeoAddressData::fromValidated($request->validated()),
        );

        return redirect()
            ->route('admin.companies.profile.edit', $company)
            ->with('success', 'Perfil de empresa actualizado.');
    }

    private function resolveCompanyAdminEmail(SecurityCompany $company): string
    {
        $admin = User::query()
            ->where('security_company_id', $company->id)
            ->whereHas('roles', fn ($q) => $q->where('name', 'company-admin'))
            ->orderBy('id')
            ->first();

        return $admin?->email ?: ($company->email ?: '—');
    }
}
