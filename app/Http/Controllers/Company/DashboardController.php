<?php

declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\SecurityCompany;
use App\Services\Company\CompanyDashboardService;
use App\Support\Platform\ActingCompanyResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly CompanyDashboardService $dashboardService,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->can('company.dashboard'), 403);

        $companyId = app(ActingCompanyResolver::class)->id($user);

        if ($user->hasRole('super-admin') && $companyId === null) {
            return redirect()->route('admin.dashboard');
        }

        abort_unless($companyId !== null && $companyId > 0, 403, 'Usuario sin empresa de seguridad asignada.');

        $company = SecurityCompany::query()->findOrFail($companyId);
        $payload = $this->dashboardService->build($company);

        return view('modules.company.dashboard', $payload);
    }
}
