<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Client;
use App\Models\Structure;
use App\Models\StructureType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class StructureRepository
{
    /** @return Collection<int, Structure> */
    public function treeForInstallation(int $clientId, int $installationId): Collection
    {
        return Structure::query()
            ->where('client_id', $clientId)
            ->where('installation_id', $installationId)
            ->whereNull('parent_id')
            ->with([
                'structureType',
                'installation',
                'children' => fn ($q) => $q->with(['structureType', 'installation', 'children.structureType'])->orderBy('name'),
            ])
            ->orderBy('name')
            ->get();
    }

    /** @return Collection<int, Structure> */
    public function treeForClient(int $clientId): Collection
    {
        return Structure::query()
            ->where('client_id', $clientId)
            ->whereNull('parent_id')
            ->with([
                'structureType',
                'installation',
                'children' => fn ($q) => $q->with(['structureType', 'installation', 'children.structureType'])->orderBy('name'),
            ])
            ->orderBy('name')
            ->get();
    }

    /** @return array<int, array{members: int, vehicles: int, pets: int}> */
    public function censusCounts(int $clientId): array
    {
        $memberCounts = DB::table('structure_members')
            ->select('structure_id', DB::raw('COUNT(*) as total'))
            ->where('client_id', $clientId)
            ->whereNull('deleted_at')
            ->groupBy('structure_id')
            ->pluck('total', 'structure_id');

        $vehicleCounts = DB::table('vehicles')
            ->select('structure_id', DB::raw('COUNT(*) as total'))
            ->where('client_id', $clientId)
            ->whereNotNull('structure_id')
            ->whereNull('deleted_at')
            ->groupBy('structure_id')
            ->pluck('total', 'structure_id');

        $petCounts = DB::table('structure_pets')
            ->select('structure_id', DB::raw('COUNT(*) as total'))
            ->where('client_id', $clientId)
            ->whereNull('deleted_at')
            ->groupBy('structure_id')
            ->pluck('total', 'structure_id');

        $structureIds = Structure::query()
            ->where('client_id', $clientId)
            ->pluck('id');

        $counts = [];
        foreach ($structureIds as $id) {
            $counts[$id] = [
                'members' => (int) ($memberCounts[$id] ?? 0),
                'vehicles' => (int) ($vehicleCounts[$id] ?? 0),
                'pets' => (int) ($petCounts[$id] ?? 0),
            ];
        }

        return $counts;
    }

    public function leafUnitsCount(int $clientId): int
    {
        $unitTypeIds = $this->unitTypeIdsForClient($clientId);

        return Structure::query()
            ->where('client_id', $clientId)
            ->whereIn('structure_type_id', $unitTypeIds)
            ->count();
    }

    /** @return Collection<int, Structure> */
    public function leafUnitsForClient(int $clientId): Collection
    {
        $unitTypeIds = $this->unitTypeIdsForClient($clientId);

        return Structure::query()
            ->where('client_id', $clientId)
            ->whereIn('structure_type_id', $unitTypeIds)
            ->with('structureType')
            ->orderBy('name')
            ->get();
    }

    /** @return \Illuminate\Support\Collection<int, int> */
    private function unitTypeIdsForClient(int $clientId)
    {
        $companyId = (int) Client::query()->whereKey($clientId)->value('security_company_id');

        return StructureType::query()
            ->where('security_company_id', $companyId)
            ->where('is_unit', true)
            ->pluck('id');
    }

    public function codeExists(int $clientId, string $code, ?int $exceptId = null): bool
    {
        $query = Structure::query()
            ->where('client_id', $clientId)
            ->where('code', $code);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->exists();
    }
}
