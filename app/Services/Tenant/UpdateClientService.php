<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Enums\PartyType;
use App\Models\Client;

final class UpdateClientService
{
    /** @param array<string, mixed> $attributes */
    public function execute(Client $client, array $attributes): Client
    {
        unset($attributes['plan_tier'], $attributes['max_structures'], $attributes['slug'], $attributes['login_suffix']);

        if (isset($attributes['party_type']) && is_string($attributes['party_type'])) {
            $attributes['party_type'] = PartyType::from($attributes['party_type']);
        }

        if (empty($attributes['legal_name']) && ! empty($attributes['name'])) {
            $attributes['legal_name'] = $attributes['name'];
        }

        $client->update($attributes);

        return $client->fresh();
    }
}
