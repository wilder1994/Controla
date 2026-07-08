<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Repositories\StructureRepository;
use App\Support\Tenancy\TenantContext;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly StructureRepository $structureRepository,
        private readonly TenantContext $tenantContext,
    ) {}

    public function index(): View
    {
        abort_unless(auth()->user()?->can('client.structures.manage'), 403);

        $clientId = (int) $this->tenantContext->clientId();
        $units = $this->structureRepository->leafUnitsCount($clientId);

        return view('modules.client.dashboard', compact('units'));
    }
}
