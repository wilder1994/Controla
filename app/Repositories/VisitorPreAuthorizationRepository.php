<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\VisitorPreAuthorization;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class VisitorPreAuthorizationRepository
{
    public function paginateForClient(int $clientId, int $perPage = 20): LengthAwarePaginator
    {
        return VisitorPreAuthorization::query()
            ->with(['structure', 'member'])
            ->where('client_id', $clientId)
            ->orderByDesc('valid_for_date')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }
}
