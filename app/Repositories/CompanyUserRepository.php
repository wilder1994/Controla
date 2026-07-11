<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final class CompanyUserRepository
{
    /** @return Collection<int, User> */
    public function operativesForCompany(int $companyId): Collection
    {
        return User::query()
            ->where('security_company_id', $companyId)
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['guardia', 'supervisor']))
            ->orderBy('name')
            ->get();
    }
}
