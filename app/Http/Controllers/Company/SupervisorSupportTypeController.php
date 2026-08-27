<?php

declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StoreSupervisorNamedCatalogRequest;
use App\Models\SupervisorSupportType;
use App\Services\Company\ManageSupervisorCompanyCatalogService;
use App\Support\Platform\ActingCompanyResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class SupervisorSupportTypeController extends Controller
{
    public function __construct(
        private readonly ManageSupervisorCompanyCatalogService $catalog,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', SupervisorSupportType::class);
        $companyId = $this->companyId($request);
        $this->catalog->ensureDefaults($companyId);

        $types = SupervisorSupportType::query()
            ->where('security_company_id', $companyId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('modules.company.supervision-catalogs.support-types', compact('types'));
    }

    public function store(StoreSupervisorNamedCatalogRequest $request): RedirectResponse
    {
        $this->catalog->createSupportType($this->companyId($request), [
            'name' => $request->validated('name'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('company.supervision-support-types.index')->with('success', 'Tipo de apoyo creado.');
    }

    public function update(StoreSupervisorNamedCatalogRequest $request, SupervisorSupportType $supportType): RedirectResponse
    {
        abort_unless((int) $supportType->security_company_id === $this->companyId($request), 404);
        $this->catalog->updateSupportType($supportType, [
            'name' => $request->validated('name'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('company.supervision-support-types.index')->with('success', 'Tipo de apoyo actualizado.');
    }

    public function destroy(Request $request, SupervisorSupportType $supportType): RedirectResponse
    {
        abort_unless((int) $supportType->security_company_id === $this->companyId($request), 404);
        $this->catalog->deleteSupportType($supportType);

        return redirect()->route('company.supervision-support-types.index')->with('success', 'Tipo de apoyo eliminado.');
    }

    private function companyId(Request $request): int
    {
        return app(ActingCompanyResolver::class)->requireId($request->user());
    }
}
