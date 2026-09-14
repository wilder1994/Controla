<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\Ops\BuildSigBoardService;
use App\Support\Tenancy\TenantContext;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly BuildSigBoardService $sigBoard,
        private readonly TenantContext $tenantContext,
    ) {}

    public function index(): View
    {
        abort_unless(auth()->user()?->can('client.structures.manage'), 403);

        return view('modules.client.dashboard', [
            'sigBoard' => $this->sigBoard->forTenant($this->tenantContext),
        ]);
    }
}
