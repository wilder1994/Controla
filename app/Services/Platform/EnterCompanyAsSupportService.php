<?php

declare(strict_types=1);

namespace App\Services\Platform;

use App\Models\SecurityCompany;
use App\Models\User;
use App\Services\Access\AuditLogger;
use App\Support\Platform\SupportCompanyContext;

final class EnterCompanyAsSupportService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function enter(User $actor, SecurityCompany $company): void
    {
        abort_unless($actor->hasRole('super-admin'), 403);

        SupportCompanyContext::enter((int) $company->id);

        $this->auditLogger->action('platform.enter_company', $company, null, [
            'security_company_id' => $company->id,
            'company_name' => $company->displayName(),
        ]);
    }

    public function exit(User $actor): ?int
    {
        abort_unless($actor->hasRole('super-admin'), 403);

        $companyId = SupportCompanyContext::companyId();
        SupportCompanyContext::exit();

        if ($companyId !== null) {
            $company = SecurityCompany::query()->find($companyId);
            $this->auditLogger->action('platform.exit_company', $company, [
                'security_company_id' => $companyId,
            ], null);
        }

        return $companyId;
    }
}
