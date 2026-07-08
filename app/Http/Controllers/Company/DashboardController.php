<?php

declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Repositories\ClientRepository;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly ClientRepository $clientRepository,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->can('company.dashboard'), 403);

        if ($user->hasRole('super-admin')) {
            return redirect()->route('admin.dashboard');
        }

        $companyId = (int) $user->security_company_id;
        abort_unless($companyId > 0, 403, 'Usuario sin empresa de seguridad asignada.');

        $metrics = $this->clientRepository->metricsForCompany($companyId);
        $recentClients = $this->clientRepository->activeForCompany($companyId)->take(5);

        return view('modules.company.dashboard', compact('metrics', 'recentClients'));
    }
}
