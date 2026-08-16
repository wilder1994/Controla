<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreStructureTypeRequest;
use App\Http\Requests\Platform\UpdateStructureTypeRequest;
use App\Models\StructureType;
use App\Services\Platform\ManageStructureTypeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class StructureTypeController extends Controller
{
    public function __construct(
        private readonly ManageStructureTypeService $manageStructureTypeService,
    ) {}

    public function index(): View
    {
        abort_unless(auth()->user()?->can('platform.settings.manage'), 403);

        $types = StructureType::query()
            ->withCount('structures')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('modules.admin.settings.structure-types.index', compact('types'));
    }

    public function store(StoreStructureTypeRequest $request): RedirectResponse
    {
        $this->manageStructureTypeService->create([
            ...$request->validated(),
            'is_unit' => $request->boolean('is_unit'),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) $request->validated('sort_order', 0),
        ]);

        return redirect()
            ->route('admin.settings.structure-types.index')
            ->with('success', 'Tipo de estructura creado.');
    }

    public function update(UpdateStructureTypeRequest $request, StructureType $structureType): RedirectResponse
    {
        $this->manageStructureTypeService->update($structureType, [
            ...$request->validated(),
            'is_unit' => $request->boolean('is_unit'),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) $request->validated('sort_order', $structureType->sort_order),
        ]);

        return redirect()
            ->route('admin.settings.structure-types.index')
            ->with('success', 'Tipo de estructura actualizado.');
    }

    public function destroy(StructureType $structureType): RedirectResponse
    {
        abort_unless(auth()->user()?->can('platform.settings.manage'), 403);

        $this->manageStructureTypeService->delete($structureType);

        return redirect()
            ->route('admin.settings.structure-types.index')
            ->with('success', 'Tipo de estructura eliminado.');
    }
}
