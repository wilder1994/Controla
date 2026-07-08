<?php

declare(strict_types=1);

namespace App\Services\Structure;

use App\Enums\StructureType;
use App\Enums\MemberType;
use App\Models\Building;
use App\Models\Client;
use App\Models\Structure;
use App\Models\StructureMember;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class MigrateLegacyStructuresService
{
    /** @var array<int, int> */
    private array $buildingMap = [];

    /** @var array<int, int> */
    private array $housingUnitMap = [];

    public function executeForClient(Client $client): array
    {
        if (Structure::query()->where('client_id', $client->id)->exists()) {
            return ['skipped' => true, 'structures' => 0, 'members' => 0];
        }

        return DB::transaction(function () use ($client): array {
            $root = Structure::query()->create([
                'client_id' => $client->id,
                'parent_id' => null,
                'name' => $client->name,
                'code' => $client->slug,
                'type' => StructureType::GeneralArea,
                'is_active' => true,
            ]);

            $structureCount = 1;
            $memberCount = 0;

            $buildings = Building::query()->where('client_id', $client->id)->get();

            foreach ($buildings as $building) {
                $block = Structure::query()->create([
                    'client_id' => $client->id,
                    'parent_id' => $root->id,
                    'name' => $building->name,
                    'code' => $building->code,
                    'type' => StructureType::Block,
                    'is_active' => $building->is_active,
                ]);
                $this->buildingMap[$building->id] = $block->id;
                $structureCount++;

                foreach ($building->housingUnits as $unit) {
                    $apartment = Structure::query()->create([
                        'client_id' => $client->id,
                        'parent_id' => $block->id,
                        'name' => "Apto {$unit->unit_number}",
                        'code' => "{$building->code}-{$unit->unit_number}",
                        'type' => StructureType::Apartment,
                        'metadata' => ['floor' => $unit->floor, 'legacy_housing_unit_id' => $unit->id],
                        'is_active' => $unit->is_active,
                    ]);
                    $this->housingUnitMap[$unit->id] = $apartment->id;
                    $structureCount++;

                    foreach ($unit->residents as $resident) {
                        if (StructureMember::query()
                            ->where('client_id', $client->id)
                            ->where('document_number', $resident->document_number)
                            ->exists()) {
                            continue;
                        }

                        StructureMember::query()->create([
                            'client_id' => $client->id,
                            'structure_id' => $apartment->id,
                            'first_name' => $resident->first_name,
                            'last_name' => $resident->last_name,
                            'document_number' => $resident->document_number,
                            'phone_primary' => $resident->phone,
                            'email' => $resident->email,
                            'member_type' => MemberType::Owner,
                            'has_app_access' => $resident->user_id !== null,
                            'access_code' => strtoupper(Str::random(12)),
                            'is_active' => $resident->is_active,
                        ]);
                        $memberCount++;
                    }
                }
            }

            return [
                'skipped' => false,
                'structures' => $structureCount,
                'members' => $memberCount,
            ];
        });
    }
}
