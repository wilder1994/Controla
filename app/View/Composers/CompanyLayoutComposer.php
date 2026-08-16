<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\Models\SecurityCompany;
use App\Repositories\ClientRepository;
use App\Support\Platform\ActingCompanyResolver;
use App\Support\Platform\SupportCompanyContext;
use Illuminate\View\View;

final class CompanyLayoutComposer
{
    public function __construct(
        private readonly ClientRepository $clientRepository,
    ) {}

    public function compose(View $view): void
    {
        $user = auth()->user();

        $companyContext = [
            'company_name' => null,
            'is_quota_full' => true,
        ];

        $supportMode = [
            'active' => false,
            'company_name' => null,
            'company_id' => null,
        ];

        if ($user !== null) {
            $companyId = app(ActingCompanyResolver::class)->id($user);

            if ($companyId !== null) {
                $metrics = $this->clientRepository->metricsForCompany($companyId);
                $companyContext = [
                    'company_name' => $metrics['company_name'],
                    'is_quota_full' => (bool) $metrics['is_quota_full'],
                ];
            }

            if ($user->hasRole('super-admin') && SupportCompanyContext::isActive()) {
                $actingId = SupportCompanyContext::companyId();
                $company = $actingId !== null
                    ? SecurityCompany::query()->find($actingId)
                    : null;

                $supportMode = [
                    'active' => true,
                    'company_name' => $company?->displayName() ?? $companyContext['company_name'],
                    'company_id' => $actingId,
                ];
            }
        }

        $view->with('companyContext', $companyContext);
        $view->with('supportMode', $supportMode);
    }
}
