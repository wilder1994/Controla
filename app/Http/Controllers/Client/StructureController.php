<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Domain\Structure\Data\CreateStructureData;
use App\Enums\StructureType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreStructureRequest;
use App\Models\Structure;
use App\Models\VisitorPreAuthorization;
use App\Repositories\StructureRepository;
use App\Services\Structure\CreateStructureService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class StructureController extends Controller
{
    public function __construct(
        private readonly StructureRepository $structureRepository,
        private readonly CreateStructureService $createStructureService,
        private readonly TenantContext $tenantContext,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Structure::class);

        $clientId = (int) $this->tenantContext->clientId();
        $tree = $this->structureRepository->treeForClient($clientId);
        $census = $this->structureRepository->censusCounts($clientId);
        $types = StructureType::options();
        $parents = Structure::query()->orderBy('name')->get();

        return view('modules.client.structures.index', compact('tree', 'census', 'types', 'parents'));
    }

    public function store(StoreStructureRequest $request): RedirectResponse
    {
        $clientId = (int) $this->tenantContext->clientId();

        $this->createStructureService->execute(new CreateStructureData(
            clientId: $clientId,
            parentId: $request->validated('parent_id'),
            name: $request->validated('name'),
            code: $request->validated('code'),
            type: StructureType::from($request->validated('type')),
            maxOccupancy: (int) $request->validated('max_occupancy', 0),
            isActive: $request->boolean('is_active', true),
        ));

        return redirect()
            ->route('client.structures.index')
            ->with('success', 'Estructura creada correctamente.');
    }

    public function show(Structure $structure): View
    {
        $this->authorize('view', $structure);

        $structure->load(['members', 'pets', 'vehicles', 'parent']);

        $authorizations = VisitorPreAuthorization::query()
            ->where('structure_id', $structure->id)
            ->latest('valid_for_date')
            ->limit(20)
            ->get();

        return view('modules.client.structures.show', compact('structure', 'authorizations'));
    }
}
