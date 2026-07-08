<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Client;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

final class ClientRepository
{
    public function paginateForCompany(int $companyId, int $perPage = 15): LengthAwarePaginator
    {
        return Client::query()
            ->where('security_company_id', $companyId)
            ->withCount('assignments')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function findForCompany(int $clientId, int $companyId): ?Client
    {
        return Client::query()
            ->where('security_company_id', $companyId)
            ->whereKey($clientId)
            ->first();
    }

    public function slugExists(int $companyId, string $slug, ?int $exceptId = null): bool
    {
        $query = Client::query()
            ->where('security_company_id', $companyId)
            ->where('slug', $slug);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->exists();
    }

    public function loginSuffixExists(int $companyId, string $suffix, ?int $exceptId = null): bool
    {
        $query = Client::query()
            ->where('security_company_id', $companyId)
            ->where('login_suffix', $suffix);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->exists();
    }

    /** @return Collection<int, Client> */
    public function activeForCompany(int $companyId): Collection
    {
        return Client::query()
            ->where('security_company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /** @return Collection<int, Client> */
    public function activeAll(): Collection
    {
        return Client::query()
            ->with('securityCompany')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function metricsForCompany(int $companyId): array
    {
        $base = Client::query()->where('security_company_id', $companyId);

        return [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('is_active', true)->count(),
            'inactive' => (clone $base)->where('is_active', false)->count(),
        ];
    }
}
