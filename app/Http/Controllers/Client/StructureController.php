<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Domain\Structure\Data\CreateStructureData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreStructureRequest;
use App\Models\Client;
use App\Models\Installation;
use App\Models\Structure;
use App\Repositories\StructureRepository;
use App\Services\Structure\CreateStructureService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class StructureController extends Controller
{
    public function __construct(
        private readonly StructureRepository $structureRepository,
        private readonly CreateStructureService $createStructureService,
        private readonly TenantContext $tenantContext,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Structure::class);

        $clientId = (int) $this->tenantContext->clientId();
        $client = Client::query()->with('structureType')->findOrFail($clientId);
        $installationsQuery = Installation::query()
            ->where('client_id', $clientId)
            ->where('is_active', true)
            ->orderByDesc('is_client_site')
            ->orderBy('name');
        $allowedIds = $this->tenantContext->installationIds();
        if ($allowedIds !== null) {
            $installationsQuery->whereIn('id', $allowedIds);
        }
        $installations = $installationsQuery->get();

        $selectedId = $request->integer('installation_id') ?: (int) $installations->first()?->id;
        $installation = $installations->firstWhere('id', $selectedId);

        $tree = $installation !== null
            ? $this->structureRepository->treeForInstallation($clientId, (int) $installation->id)
            : collect();
        $census = $this->structureRepository->censusCounts($clientId);
        $parentOptions = $installation !== null
            ? $this->structureRepository->parentOptionsForInstallation($clientId, (int) $installation->id)
            : [];

        return view('modules.client.structures.index', compact(
            'client',
            'installations',
            'installation',
            'tree',
            'census',
            'parentOptions',
        ));
    }

    public function store(StoreStructureRequest $request): RedirectResponse
    {
        $clientId = (int) $this->tenantContext->clientId();
        $installationId = (int) $request->validated('installation_id');

        $this->createStructureService->execute(new CreateStructureData(
            clientId: $clientId,
            installationId: $installationId,
            parentId: $request->validated('parent_id') !== null
                ? (int) $request->validated('parent_id')
                : null,
            name: $request->validated('name'),
            maxOccupancy: (int) $request->validated('max_occupancy', 0),
            isActive: $request->boolean('is_active', true),
        ));

        return redirect()
            ->route('client.structures.index', ['installation_id' => $installationId])
            ->with('success', 'Estructura creada correctamente.');
    }

    public function show(Structure $structure): View
    {
        $this->authorize('view', $structure);

        $structure->load(['members.memberType', 'pets', 'vehicles', 'parent', 'structureType', 'installation']);

        return view('modules.client.structures.show', compact('structure'));
    }
}
