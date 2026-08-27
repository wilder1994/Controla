<?php

declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StoreSupervisorNamedCatalogRequest;
use App\Models\SupervisorWeaponType;
use App\Services\Company\ManageSupervisorCompanyCatalogService;
use App\Support\Platform\ActingCompanyResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class SupervisorWeaponTypeController extends Controller
{
    public function __construct(
        private readonly ManageSupervisorCompanyCatalogService $catalog,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', SupervisorWeaponType::class);
        $companyId = $this->companyId($request);
        $this->catalog->ensureDefaults($companyId);

        $types = SupervisorWeaponType::query()
            ->where('security_company_id', $companyId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('modules.company.supervision-catalogs.weapon-types', compact('types'));
    }

    public function store(StoreSupervisorNamedCatalogRequest $request): RedirectResponse
    {
        $this->catalog->createWeaponType($this->companyId($request), [
            'name' => $request->validated('name'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('company.supervision-weapon-types.index')->with('success', 'Tipo de arma creado.');
    }

    public function update(StoreSupervisorNamedCatalogRequest $request, SupervisorWeaponType $weaponType): RedirectResponse
    {
        abort_unless((int) $weaponType->security_company_id === $this->companyId($request), 404);
        $this->catalog->updateWeaponType($weaponType, [
            'name' => $request->validated('name'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('company.supervision-weapon-types.index')->with('success', 'Tipo de arma actualizado.');
    }

    public function destroy(Request $request, SupervisorWeaponType $weaponType): RedirectResponse
    {
        abort_unless((int) $weaponType->security_company_id === $this->companyId($request), 404);
        $this->catalog->deleteWeaponType($weaponType);

        return redirect()->route('company.supervision-weapon-types.index')->with('success', 'Tipo de arma eliminado.');
    }

    private function companyId(Request $request): int
    {
        return app(ActingCompanyResolver::class)->requireId($request->user());
    }
}
