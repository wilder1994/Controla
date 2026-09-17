<?php

declare(strict_types=1);

namespace App\Http\Controllers\Access;

use App\Http\Controllers\Controller;
use App\Repositories\VisitorPreAuthorizationRepository;
use App\Support\Tenancy\TenantContext;
use Illuminate\View\View;

final class PorteriaAuthorizationController extends Controller
{
    public function index(TenantContext $tenant, VisitorPreAuthorizationRepository $repo): View
    {
        return view('modules.access.authorizations.index', [
            'authorizations' => $repo->paginateForClient((int) $tenant->clientId()),
        ]);
    }
}
