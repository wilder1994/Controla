<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Enums\ClientPlanTier;
use App\Models\Client;
use Illuminate\Support\Str;

final class UpdateClientService
{
    /** @param array<string, mixed> $attributes */
    public function execute(Client $client, array $attributes): Client
    {
        if (isset($attributes['slug'])) {
            $attributes['slug'] = Str::slug((string) $attributes['slug']);
        }

        if (isset($attributes['login_suffix'])) {
            $attributes['login_suffix'] = Str::lower((string) $attributes['login_suffix']);
        }

        if (isset($attributes['plan_tier'])) {
            $tier = $attributes['plan_tier'] instanceof ClientPlanTier
                ? $attributes['plan_tier']
                : ClientPlanTier::from((string) $attributes['plan_tier']);

            $attributes['plan_tier'] = $tier;
            $attributes['max_structures'] = $tier->maxStructures();
        }

        $client->update($attributes);

        return $client->fresh();
    }
}
