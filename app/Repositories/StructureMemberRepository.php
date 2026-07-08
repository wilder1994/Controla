<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\StructureMember;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class StructureMemberRepository
{
    public function paginateForClient(
        int $clientId,
        ?string $search = null,
        ?int $structureId = null,
        int $perPage = 20,
    ): LengthAwarePaginator {
        $query = StructureMember::query()
            ->with('structure')
            ->where('client_id', $clientId)
            ->orderBy('last_name')
            ->orderBy('first_name');

        if ($search) {
            $query->where(function ($q) use ($search): void {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($structureId) {
            $query->where('structure_id', $structureId);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function documentExists(int $clientId, string $documentNumber, ?int $exceptId = null): bool
    {
        $query = StructureMember::query()
            ->where('client_id', $clientId)
            ->where('document_number', $documentNumber);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->exists();
    }
}
