<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreIdentityDocumentTypeRequest;
use App\Http\Requests\Platform\UpdateIdentityDocumentTypeRequest;
use App\Models\IdentityDocumentType;
use App\Services\Platform\ManageIdentityDocumentTypeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class IdentityDocumentTypeController extends Controller
{
    public function __construct(
        private readonly ManageIdentityDocumentTypeService $manageIdentityDocumentTypeService,
    ) {}

    public function index(): View
    {
        abort_unless(auth()->user()?->can('platform.settings.manage'), 403);

        $types = IdentityDocumentType::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('modules.admin.settings.document-types.index', compact('types'));
    }

    public function store(StoreIdentityDocumentTypeRequest $request): RedirectResponse
    {
        $this->manageIdentityDocumentTypeService->create([
            'name' => $request->validated('name'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('admin.settings.document-types.index')
            ->with('success', 'Tipo de documento creado.');
    }

    public function update(UpdateIdentityDocumentTypeRequest $request, IdentityDocumentType $documentType): RedirectResponse
    {
        $this->manageIdentityDocumentTypeService->update($documentType, [
            'name' => $request->validated('name'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.settings.document-types.index')
            ->with('success', 'Tipo de documento actualizado.');
    }

    public function moveUp(IdentityDocumentType $documentType): RedirectResponse
    {
        abort_unless(auth()->user()?->can('platform.settings.manage'), 403);
        $this->manageIdentityDocumentTypeService->moveUp($documentType);

        return redirect()->route('admin.settings.document-types.index');
    }

    public function moveDown(IdentityDocumentType $documentType): RedirectResponse
    {
        abort_unless(auth()->user()?->can('platform.settings.manage'), 403);
        $this->manageIdentityDocumentTypeService->moveDown($documentType);

        return redirect()->route('admin.settings.document-types.index');
    }

    public function destroy(IdentityDocumentType $documentType): RedirectResponse
    {
        abort_unless(auth()->user()?->can('platform.settings.manage'), 403);
        $this->manageIdentityDocumentTypeService->delete($documentType);

        return redirect()
            ->route('admin.settings.document-types.index')
            ->with('success', 'Tipo de documento eliminado.');
    }
}
