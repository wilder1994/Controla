<?php

declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StoreSupervisorNamedCatalogRequest;
use App\Models\SupervisorControlBookType;
use App\Services\Company\ManageSupervisorCompanyCatalogService;
use App\Support\Platform\ActingCompanyResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class SupervisorControlBookTypeController extends Controller
{
    public function __construct(
        private readonly ManageSupervisorCompanyCatalogService $catalog,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', SupervisorControlBookType::class);
        $companyId = $this->companyId($request);
        $this->catalog->ensureDefaults($companyId);

        $types = SupervisorControlBookType::query()
            ->where('security_company_id', $companyId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('modules.company.supervision-catalogs.control-book-types', compact('types'));
    }

    public function store(StoreSupervisorNamedCatalogRequest $request): RedirectResponse
    {
        $this->catalog->createControlBookType($this->companyId($request), [
            'name' => $request->validated('name'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('company.supervision-control-book-types.index')->with('success', 'Tipo de libro creado.');
    }

    public function update(StoreSupervisorNamedCatalogRequest $request, SupervisorControlBookType $controlBookType): RedirectResponse
    {
        abort_unless((int) $controlBookType->security_company_id === $this->companyId($request), 404);
        $this->catalog->updateControlBookType($controlBookType, [
            'name' => $request->validated('name'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('company.supervision-control-book-types.index')->with('success', 'Tipo de libro actualizado.');
    }

    public function destroy(Request $request, SupervisorControlBookType $controlBookType): RedirectResponse
    {
        abort_unless((int) $controlBookType->security_company_id === $this->companyId($request), 404);
        $this->catalog->deleteControlBookType($controlBookType);

        return redirect()->route('company.supervision-control-book-types.index')->with('success', 'Tipo de libro eliminado.');
    }

    private function companyId(Request $request): int
    {
        return app(ActingCompanyResolver::class)->requireId($request->user());
    }
}
