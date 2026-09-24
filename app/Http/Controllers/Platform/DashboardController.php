<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\SecurityCompany;
use App\Services\Platform\PlatformDashboardService;
use App\Services\Platform\ReleaseClientService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly PlatformDashboardService $platformDashboardService,
        private readonly ReleaseClientService $releaseClientService,
    ) {}

    public function index(Request $request): View
    {
        abort_unless(auth()->user()?->can('platform.dashboard'), 403);

        return view('modules.admin.dashboard', $this->platformDashboardService->build($request));
    }

    public function releaseClient(Request $request, SecurityCompany $company, Client $client): RedirectResponse
    {
        abort_unless(auth()->user()?->can('platform.companies.manage'), 403);
        abort_unless((int) $client->security_company_id === (int) $company->id, 404);

        $this->releaseClientService->execute($client);

        return redirect()
            ->route('admin.dashboard')
            ->with('success', "Conjunto «{$client->name}» retirado. Cupo liberado.");
    }
}
