<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Vehicle;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class StructureVehicleRepository
{
    public function paginateForClient(
        int $clientId,
        ?string $search = null,
        ?int $structureId = null,
        ?int $installationId = null,
        ?array $allowedInstallationIds = null,
        int $perPage = 20,
    ): LengthAwarePaginator {
        $query = Vehicle::query()
            ->with('structure.installation')
            ->where('client_id', $clientId)
            ->whereNotNull('structure_id')
            ->orderBy('plate');

        if ($search) {
            $query->where(function ($q) use ($search): void {
                $q->where('plate', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%");
            });
        }

        if ($structureId) {
            $query->where('structure_id', $structureId);
        } elseif ($installationId) {
            $query->whereHas('structure', fn ($q) => $q->where('installation_id', $installationId));
        } elseif ($allowedInstallationIds !== null) {
            $query->whereHas('structure', fn ($q) => $q->whereIn('installation_id', $allowedInstallationIds));
        }

        return $query->paginate($perPage)->withQueryString();
    }
}
