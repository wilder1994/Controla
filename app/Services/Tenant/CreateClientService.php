<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Domain\Tenant\Data\CreateClientData;
use App\Enums\ClientLifecycle;
use App\Enums\ClientPlanTier;
use App\Models\Client;
use App\Models\SecurityCompany;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CreateClientService
{
    public function execute(CreateClientData $data): Client
    {
        $company = SecurityCompany::query()->findOrFail($data->securityCompanyId);
        $this->assertWithinQuota($company);

        return Client::query()->create([
            'security_company_id' => $data->securityCompanyId,
            'name' => $data->name,
            'slug' => Str::slug($data->slug),
            'login_suffix' => Str::lower($data->loginSuffix),
            'address' => $data->address,
            'city' => $data->city,
            'department' => $data->department,
            'latitude' => $data->latitude,
            'longitude' => $data->longitude,
            // Legacy columns retained; portfolio is unlimited commercially.
            'plan_tier' => ClientPlanTier::Economic,
            'max_structures' => ClientPlanTier::Economic->maxStructures(),
            'access_url' => $data->accessUrl,
            'is_active' => $data->isActive,
            'service_started_at' => $data->serviceStartedAt ?? now()->toDateString(),
            'lifecycle' => ClientLifecycle::Active,
        ]);
    }

    private function assertWithinQuota(SecurityCompany $company): void
    {
        $maxClients = (int) ($company->max_clients ?: 10);
        $current = $company->operationalClientsCount();

        if ($current >= $maxClients) {
            throw ValidationException::withMessages([
                'name' => "Has alcanzado el cupo de tu paquete ({$current}/{$maxClients} clientes). Amplía el paquete desde plataforma.",
            ]);
        }
    }
}
