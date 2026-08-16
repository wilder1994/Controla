<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Structure;
use App\Models\StructureType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class StructureRepository
{
    /** @return Collection<int, Structure> */
    public function treeForClient(int $clientId): Collection
    {
        return Structure::query()
            ->where('client_id', $clientId)
            ->whereNull('parent_id')
            ->with([
                'structureType',
                'children' => fn ($q) => $q->with(['structureType', 'children.structureType'])->orderBy('name'),
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
        $unitTypeIds = StructureType::query()->where('is_unit', true)->pluck('id');

        return Structure::query()
            ->where('client_id', $clientId)
            ->whereIn('structure_type_id', $unitTypeIds)
            ->count();
    }

    /** @return Collection<int, Structure> */
    public function leafUnitsForClient(int $clientId): Collection
    {
        $unitTypeIds = StructureType::query()->where('is_unit', true)->pluck('id');

        return Structure::query()
            ->where('client_id', $clientId)
            ->whereIn('structure_type_id', $unitTypeIds)
            ->with('structureType')
            ->orderBy('name')
            ->get();
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
