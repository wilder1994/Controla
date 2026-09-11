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

    /** @var list<int>|null */
    private ?array $installationIds = null;

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

    /** @return list<int>|null */
    public function installationIds(): ?array
    {
        return $this->installationIds;
    }

    /** @param list<int>|null $installationIds */
    public function setInstallationIds(?array $installationIds): void
    {
        $this->installationIds = $installationIds === null
            ? null
            : array_values(array_unique(array_map('intval', $installationIds)));
    }

    public function allowsInstallation(int $installationId): bool
    {
        return $this->installationIds === null || in_array($installationId, $this->installationIds, true);
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
        $this->installationIds = null;
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
        $this->setInstallationIds($user->assignedInstallationIds());

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
        } elseif ($user->primary_client_id !== null && in_array((int) $user->primary_client_id, $allowedClientIds, true)) {
            $this->setClientId((int) $user->primary_client_id);
        }
    }
}
