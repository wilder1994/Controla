<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use App\Services\Auth\UserScopeResolver;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class UserRepository
{
    public function __construct(
        private readonly UserScopeResolver $scopeResolver,
    ) {}

    public function paginateScoped(User $actor, int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        $query = $this->scopeResolver->scopedQuery($actor)
            ->with(['roles', 'securityCompany', 'clients'])
            ->orderBy('name');

        if ($search !== null && $search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }
}
