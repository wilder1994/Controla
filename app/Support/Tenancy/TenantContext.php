<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use App\Models\Client;
use App\Models\SecurityCompany;
use App\Models\User;

final class TenantContext
{
    private ?int $companyId = null;

    private ?int $clientId = null;

    private bool $scopingEnabled = true;

    public function setCompany(?SecurityCompany $company): void
    {
        $this->companyId = $company?->id;
    }

    public function setCompanyId(?int $companyId): void
    {
        $this->companyId = $companyId;
    }

    public function setClient(?Client $client): void
    {
        $this->clientId = $client?->id;
        $this->companyId = $client?->security_company_id ?? $this->companyId;
    }

    public function setClientId(?int $clientId): void
    {
        $this->clientId = $clientId;
    }

    public function companyId(): ?int
    {
        return $this->companyId;
    }

    public function clientId(): ?int
    {
        return $this->clientId;
    }

    public function disableScoping(): void
    {
        $this->scopingEnabled = false;
    }

    public function enableScoping(): void
    {
        $this->scopingEnabled = true;
    }

    public function isScopingEnabled(): bool
    {
        return $this->scopingEnabled;
    }

    public function clear(): void
    {
        $this->companyId = null;
        $this->clientId = null;
        $this->scopingEnabled = true;
    }

    public function hydrateForUser(User $user, ?int $requestedClientId = null): void
    {
        $this->clear();

        if ($user->hasRole('super-admin')) {
            if ($requestedClientId !== null) {
                $client = Client::query()->find($requestedClientId);
                $this->setClient($client);
            }

            return;
        }

        if ($user->hasRole('company-admin') && $user->security_company_id) {
            $this->setCompanyId((int) $user->security_company_id);

            if ($requestedClientId !== null) {
                $client = Client::query()
                    ->where('security_company_id', $user->security_company_id)
                    ->whereKey($requestedClientId)
                    ->first();

                if ($client !== null) {
                    $this->setClient($client);
                }
            }

            return;
        }

        $allowedClientIds = $user->assignedClientIds();

        if ($requestedClientId !== null) {
            if (! in_array($requestedClientId, $allowedClientIds, true)) {
                return;
            }

            $client = Client::query()->find($requestedClientId);
            $this->setClient($client);

            return;
        }

        if (count($allowedClientIds) === 1) {
            $this->setClientId($allowedClientIds[0]);
        }
    }
}
