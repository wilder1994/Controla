<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Installation;
use App\Repositories\StructureRepository;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class InstallationController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly StructureRepository $structureRepository,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', \App\Models\Structure::class);

        $clientId = (int) $this->tenantContext->clientId();
        $search = $request->string('q')->trim()->toString();

        $rows = $this->scopedQuery($clientId)
            ->with('rector')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhere('code', 'like', '%'.$search.'%')
                        ->orWhere('commune', 'like', '%'.$search.'%')
                        ->orWhereHas('rector', fn ($r) => $r->where('name', 'like', '%'.$search.'%')
                            ->orWhere('job_title', 'like', '%'.$search.'%'));
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('modules.client.installations.index', [
            'installations' => $rows,
            'search' => $search,
        ]);
    }

    public function show(Installation $installation): View
    {
        $this->authorize('viewAny', \App\Models\Structure::class);
        $this->assertVisible($installation);

        $clientId = (int) $this->tenantContext->clientId();
        $client = Client::query()->with('structureType')->findOrFail($clientId);
        $installation->load('rector');

        $tree = $this->structureRepository->treeForInstallation($clientId, (int) $installation->id);
        $census = $this->structureRepository->censusCounts($clientId);
        $parentOptions = $this->structureRepository->parentOptionsForInstallation($clientId, (int) $installation->id);

        return view('modules.client.installations.show', [
            'client' => $client,
            'installation' => $installation,
            'tree' => $tree,
            'census' => $census,
            'parentOptions' => $parentOptions,
            'maps' => [
                'api_key' => config('google-maps.api_key'),
                'zoom' => 17,
            ],
        ]);
    }

    private function scopedQuery(int $clientId)
    {
        $query = Installation::query()
            ->where('client_id', $clientId)
            ->where('is_active', true);

        $allowedIds = $this->tenantContext->installationIds();
        if ($allowedIds !== null) {
            $query->whereIn('id', $allowedIds);
        }

        return $query;
    }

    private function assertVisible(Installation $installation): void
    {
        $clientId = (int) $this->tenantContext->clientId();
        abort_unless((int) $installation->client_id === $clientId, 404);
        abort_unless($this->tenantContext->allowsInstallation((int) $installation->id), 403);
    }
}
