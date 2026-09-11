<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\StructurePet;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

final class StructurePetRepository
{
    public function paginateForClient(
        int $clientId,
        ?string $search = null,
        ?int $structureId = null,
        ?int $installationId = null,
        int $perPage = 20,
    ): LengthAwarePaginator {
        $query = StructurePet::query()
            ->with('structure.installation')
            ->where('client_id', $clientId)
            ->orderBy('name');

        if ($search) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('breed', 'like', "%{$search}%");
            });
        }

        if ($structureId) {
            $query->where('structure_id', $structureId);
        } elseif ($installationId) {
            $query->whereHas('structure', fn ($q) => $q->where('installation_id', $installationId));
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function forClient(int $clientId): Collection
    {
        return StructurePet::query()
            ->with('structure')
            ->where('client_id', $clientId)
            ->orderBy('name')
            ->get();
    }
}
