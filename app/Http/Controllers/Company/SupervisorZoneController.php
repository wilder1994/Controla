<?php

declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StoreSupervisorNamedCatalogRequest;
use App\Models\SupervisorZone;
use App\Services\Company\ManageSupervisorCompanyCatalogService;
use App\Support\Platform\ActingCompanyResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class SupervisorZoneController extends Controller
{
    public function __construct(
        private readonly ManageSupervisorCompanyCatalogService $catalog,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', SupervisorZone::class);
        $companyId = $this->companyId($request);
        $this->catalog->ensureDefaults($companyId);

        $zones = SupervisorZone::query()
            ->where('security_company_id', $companyId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('modules.company.supervision-catalogs.zones', compact('zones'));
    }

    public function store(StoreSupervisorNamedCatalogRequest $request): RedirectResponse
    {
        $this->catalog->createZone($this->companyId($request), [
            'name' => $request->validated('name'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('company.supervision-zones.index')->with('success', 'Zona creada.');
    }

    public function update(StoreSupervisorNamedCatalogRequest $request, SupervisorZone $zone): RedirectResponse
    {
        abort_unless((int) $zone->security_company_id === $this->companyId($request), 404);
        $this->catalog->updateZone($zone, [
            'name' => $request->validated('name'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('company.supervision-zones.index')->with('success', 'Zona actualizada.');
    }

    public function destroy(Request $request, SupervisorZone $zone): RedirectResponse
    {
        abort_unless((int) $zone->security_company_id === $this->companyId($request), 404);
        $this->catalog->deleteZone($zone);

        return redirect()->route('company.supervision-zones.index')->with('success', 'Zona eliminada.');
    }

    private function companyId(Request $request): int
    {
        return app(ActingCompanyResolver::class)->requireId($request->user());
    }
}
