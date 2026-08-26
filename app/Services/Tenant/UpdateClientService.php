<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Enums\PartyType;
use App\Models\Client;
use App\Models\SecurityCompany;

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

        $hasAccess = array_key_exists('has_access', $attributes)
            ? $this->toBool($attributes['has_access'])
            : (bool) $client->has_access;
        $hasSupervision = array_key_exists('has_supervision', $attributes)
            ? $this->toBool($attributes['has_supervision'])
            : (bool) $client->has_supervision;

        $company = SecurityCompany::query()->findOrFail($client->security_company_id);
        app(AssertClientServiceSeats::class)->execute($company, $hasAccess, $hasSupervision, $client->id);

        if (array_key_exists('has_access', $attributes)) {
            $attributes['has_access'] = $hasAccess;
        }
        if (array_key_exists('has_supervision', $attributes)) {
            $attributes['has_supervision'] = $hasSupervision;
        }

        $client->update($attributes);

        return $client->fresh();
    }

    private function toBool(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
