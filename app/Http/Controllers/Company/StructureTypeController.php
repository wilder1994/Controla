<?php

declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StoreCompanyStructureTypeRequest;
use App\Http\Requests\Company\UpdateCompanyStructureTypeRequest;
use App\Models\StructureType;
use App\Services\Company\ManageCompanyStructureTypeService;
use App\Support\Platform\ActingCompanyResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class StructureTypeController extends Controller
{
    public function __construct(
        private readonly ManageCompanyStructureTypeService $manageCompanyStructureTypeService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', StructureType::class);
        $companyId = $this->companyId($request);

        $types = StructureType::query()
            ->where('security_company_id', $companyId)
            ->withCount(['clients', 'structures'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('modules.company.structure-types.index', compact('types'));
    }

    public function store(StoreCompanyStructureTypeRequest $request): RedirectResponse
    {
        $this->manageCompanyStructureTypeService->create($this->companyId($request), [
            'name' => $request->validated('name'),
            'is_active' => $request->boolean('is_active', true),
            'is_unit' => $request->boolean('is_unit'),
        ]);

        return redirect()
            ->route('company.structure-types.index')
            ->with('success', 'Tipo de estructura creado.');
    }

    public function update(UpdateCompanyStructureTypeRequest $request, StructureType $structureType): RedirectResponse
    {
        $this->assertCompany($request, $structureType);
        $this->manageCompanyStructureTypeService->update($structureType, [
            'name' => $request->validated('name'),
            'is_active' => $request->boolean('is_active'),
            'is_unit' => $request->boolean('is_unit'),
        ]);

        return redirect()
            ->route('company.structure-types.index')
            ->with('success', 'Tipo de estructura actualizado.');
    }

    public function destroy(Request $request, StructureType $structureType): RedirectResponse
    {
        $this->assertCompany($request, $structureType);
        $this->authorize('delete', $structureType);

        try {
            $this->manageCompanyStructureTypeService->delete($structureType);
        } catch (ValidationException $e) {
            return redirect()
                ->route('company.structure-types.index')
                ->with('error', $e->validator->errors()->first() ?: 'No se pudo eliminar el tipo.');
        }

        return redirect()
            ->route('company.structure-types.index')
            ->with('success', 'Tipo de estructura eliminado.');
    }

    private function companyId(Request $request): int
    {
        return app(ActingCompanyResolver::class)->requireId($request->user());
    }

    private function assertCompany(Request $request, StructureType $structureType): void
    {
        abort_unless((int) $structureType->security_company_id === $this->companyId($request), 404);
    }
};
