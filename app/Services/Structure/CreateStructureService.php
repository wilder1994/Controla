<?php

declare(strict_types=1);

namespace App\Services\Structure;

use App\Domain\Structure\Data\CreateStructureData;
use App\Models\Client;
use App\Models\Structure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreateStructureService
{
    public function execute(CreateStructureData $data): Structure
    {
        return DB::transaction(function () use ($data): Structure {
            $client = Client::query()->findOrFail($data->clientId);
            $structureTypeId = $client->structure_type_id ?? $data->structureTypeId;

            if ($structureTypeId === null) {
                throw ValidationException::withMessages([
                    'name' => 'Este cliente no tiene tipo de estructura asignado. Configúralo en la ficha del cliente.',
                ]);
            }

            return Structure::query()->create([
                'client_id' => $data->clientId,
                'parent_id' => $data->parentId,
                'name' => $data->name,
                'code' => $data->code,
                'structure_type_id' => (int) $structureTypeId,
                'max_occupancy' => $data->maxOccupancy,
                'is_active' => $data->isActive,
                'metadata' => $data->metadata,
            ]);
        });
    }
}
