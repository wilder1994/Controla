<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Enums\BillingCycle;
use App\Enums\ClientLifecycle;
use App\Enums\CompanyPackageSku;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\UpdateCompanyPackageRequest;
use App\Models\SecurityCompany;
use App\Repositories\SecurityCompanyRepository;
use App\Services\Pricing\PriceCalculator;
use App\Services\Tenant\AssignCompanyPackageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class CompanyController extends Controller
{
    public function __construct(
        private readonly SecurityCompanyRepository $securityCompanyRepository,
        private readonly AssignCompanyPackageService $assignCompanyPackageService,
        private readonly PriceCalculator $priceCalculator,
    ) {}

    public function index(): View
    {
        abort_unless(auth()->user()?->can('platform.companies.view'), 403);

        $companies = $this->securityCompanyRepository->paginate();
        $kpis = $this->securityCompanyRepository->companiesIndexKpis();

        return view('modules.admin.companies.index', compact('companies', 'kpis'));
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

        return view('modules.admin.companies.show', compact(
            'company',
            'packageOptions',
            'cycleOptions',
            'quote',
            'quoteAnnual',
        ));
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
}
