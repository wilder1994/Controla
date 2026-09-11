<?php

declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Domain\Geo\GeoAddressData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\UpdateCompanySettingsRequest;
use App\Models\SecurityCompany;
use App\Services\Tenant\UpdateCompanyProfileService;
use App\Support\Platform\ActingCompanyResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class SettingsController extends Controller
{
    public function __construct(
        private readonly UpdateCompanyProfileService $updateCompanyProfileService,
    ) {}

    public function edit(Request $request): View
    {
        $company = $this->resolveCompany($request);

        return view('modules.company.settings.edit', [
            'company' => $company,
            'logoPreviewUrl' => $company->logo_path
                ? route('company.settings.logo').'?v='.$company->updated_at?->timestamp
                : null,
        ]);
    }

    public function logo(Request $request): BinaryFileResponse
    {
        $company = $this->resolveCompany($request);

        return $company->logoFileResponse() ?? abort(404);
    }

    public function update(UpdateCompanySettingsRequest $request): RedirectResponse
    {
        $company = $this->resolveCompany($request);

        $this->updateCompanyProfileService->assertTaxIdImmutable($company, $request->input('tax_id'));

        $this->updateCompanyProfileService->execute(
            $company,
            $request->safe()->except([...GeoAddressData::formKeys(), 'logo', 'remove_logo']),
            GeoAddressData::fromValidated($request->validated()),
            $request->file('logo'),
            $request->boolean('remove_logo'),
        );

        return redirect()
            ->route('company.settings.edit')
            ->with('success', 'Datos de la empresa actualizados.');
    }

    private function resolveCompany(Request $request): SecurityCompany
    {
        $companyId = app(ActingCompanyResolver::class)->requireId($request->user());

        return SecurityCompany::query()->findOrFail($companyId);
    }
}
