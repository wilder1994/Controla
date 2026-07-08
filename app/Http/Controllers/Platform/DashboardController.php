<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Repositories\SecurityCompanyRepository;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly SecurityCompanyRepository $securityCompanyRepository,
    ) {}

    public function index(): View
    {
        abort_unless(auth()->user()?->can('platform.dashboard'), 403);

        $metrics = $this->securityCompanyRepository->platformMetrics();
        $recentCompanies = $this->securityCompanyRepository->recentCompanies();

        return view('modules.admin.dashboard', compact('metrics', 'recentCompanies'));
    }
}
