<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Enums\ClientLifecycle;
use App\Models\Client;
use App\Models\SecurityCompany;
use Illuminate\Validation\ValidationException;

final class AssertClientServiceSeats
{
    public function execute(
        SecurityCompany $company,
        bool $hasAccess,
        bool $hasSupervision,
        ?int $exceptClientId = null,
    ): void {
        if ($hasAccess) {
            $max = (int) ($company->max_clients ?: 0);
            $used = $company->accessSeatsCount($exceptClientId);
            if ($max < 1 || $used >= $max) {
                throw ValidationException::withMessages([
                    'has_access' => 'Cupo de Accesos lleno ('.$used.'/'.$max.'). Puedes guardar la ficha sin operar accesos.',
                ]);
            }
        }

        if ($hasSupervision) {
            if ($company->hasUnlimitedSupervision()) {
                return;
            }

            $max = (int) ($company->max_supervision_clients ?: 0);
            $used = $company->supervisionSeatsCount($exceptClientId);
            if ($max < 1 || $used >= $max) {
                throw ValidationException::withMessages([
                    'has_supervision' => 'Cupo de Supervisión lleno ('.$used.'/'.$max.'). Contrata Supervisión o deja la ficha sin esa línea.',
                ]);
            }
        }
    }

    public function catalogCount(SecurityCompany $company): int
    {
        return Client::query()
            ->where('security_company_id', $company->id)
            ->where('lifecycle', ClientLifecycle::Active)
            ->count();
    }
}
