<?php

declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Domain\Geo\GeoAddressData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\UpdateCompanySettingsRequest;
use App\Models\SecurityCompany;
use App\Services\Tenant\UpdateCompanyProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class SettingsController extends Controller
{
    public function __construct(
        private readonly UpdateCompanyProfileService $updateCompanyProfileService,
    ) {}

    public function edit(Request $request): View
    {
        $company = SecurityCompany::query()->findOrFail($request->user()->security_company_id);
        $this->authorize('updateProfile', $company);

        return view('modules.company.settings.edit', compact('company'));
    }

    public function update(UpdateCompanySettingsRequest $request): RedirectResponse
    {
        $company = SecurityCompany::query()->findOrFail($request->user()->security_company_id);
        $this->authorize('updateProfile', $company);

        $this->updateCompanyProfileService->assertTaxIdImmutable($company, $request->input('tax_id'));

        $this->updateCompanyProfileService->execute(
            $company,
            $request->safe()->except(['address', 'latitude', 'longitude']),
            GeoAddressData::fromValidated($request->validated()),
        );

        return redirect()
            ->route('company.settings.edit')
            ->with('success', 'Datos de la empresa actualizados.');
    }
}
