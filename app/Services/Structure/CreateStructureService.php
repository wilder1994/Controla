<?php

declare(strict_types=1);

namespace App\Services\Structure;

use App\Domain\Structure\Data\CreateStructureData;
use App\Models\Structure;
use Illuminate\Support\Facades\DB;

final class CreateStructureService
{
    public function execute(CreateStructureData $data): Structure
    {
        return DB::transaction(function () use ($data): Structure {
            return Structure::query()->create([
                'client_id' => $data->clientId,
                'parent_id' => $data->parentId,
                'name' => $data->name,
                'code' => $data->code,
                'type' => $data->type,
                'max_occupancy' => $data->maxOccupancy,
                'is_active' => $data->isActive,
                'metadata' => $data->metadata,
            ]);
        });
    }
}
