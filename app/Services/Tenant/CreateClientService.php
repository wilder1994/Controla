<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Domain\Tenant\Data\CreateClientData;
use App\Models\Client;
use Illuminate\Support\Str;

final class CreateClientService
{
    public function execute(CreateClientData $data): Client
    {
        return Client::query()->create([
            'security_company_id' => $data->securityCompanyId,
            'name' => $data->name,
            'slug' => Str::slug($data->slug),
            'login_suffix' => Str::lower($data->loginSuffix),
            'plan_tier' => $data->planTier,
            'max_structures' => $data->planTier->maxStructures(),
            'access_url' => $data->accessUrl,
            'is_active' => $data->isActive,
        ]);
    }
}
