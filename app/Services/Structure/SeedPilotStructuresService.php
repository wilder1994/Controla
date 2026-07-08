<?php

declare(strict_types=1);

namespace App\Services\Structure;

use App\Models\Client;
use App\Models\Structure;
use App\Models\StructureAppUser;
use App\Models\StructureMember;
use App\Models\StructurePet;
use App\Models\Vehicle;
use App\Models\VisitorPreAuthorization;
use App\Enums\AuthorizationStatus;
use App\Enums\MemberType;
use App\Enums\PetSpecies;
use App\Enums\StructureType;
use App\Enums\VisitorCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class SeedPilotStructuresService
{
    public function execute(Client $client): void
    {
        if (Structure::query()->where('client_id', $client->id)->where('code', 'TORRE-A')->exists()) {
            return;
        }

        DB::transaction(function () use ($client): void {
            $root = Structure::query()->firstOrCreate(
                ['client_id' => $client->id, 'code' => $client->slug],
                [
                    'parent_id' => null,
                    'name' => $client->name,
                    'type' => StructureType::GeneralArea,
                    'is_active' => true,
                ]
            );

            $tower = Structure::query()->firstOrCreate(
                ['client_id' => $client->id, 'code' => 'TORRE-A'],
                [
                    'parent_id' => $root->id,
                    'name' => 'Torre A',
                    'type' => StructureType::Block,
                    'is_active' => true,
                ]
            );

            $apartments = [];
            for ($i = 1; $i <= 10; $i++) {
                $code = sprintf('TORRE-A-%03d', $i);
                $apartments[] = Structure::query()->firstOrCreate(
                    ['client_id' => $client->id, 'code' => $code],
                    [
                        'parent_id' => $tower->id,
                        'name' => "Apto {$i}01",
                        'type' => StructureType::Apartment,
                        'max_occupancy' => 4,
                        'is_active' => true,
                    ]
                );
            }

            $memberTypes = [MemberType::Owner, MemberType::Tenant, MemberType::FamilyMember];
            $memberIndex = 0;

            foreach ($apartments as $apartment) {
                for ($j = 0; $j < 2; $j++) {
                    $memberIndex++;
                    $doc = sprintf('1000%06d', $memberIndex);

                    StructureMember::query()->firstOrCreate(
                        ['client_id' => $client->id, 'document_number' => $doc],
                        [
                            'structure_id' => $apartment->id,
                            'first_name' => "Persona{$memberIndex}",
                            'last_name' => 'Piloto',
                            'phone_primary' => '+57300'.str_pad((string) $memberIndex, 7, '0', STR_PAD_LEFT),
                            'email' => "persona{$memberIndex}@piloto.test",
                            'member_type' => $memberTypes[$j % 3],
                            'has_app_access' => $j === 0,
                            'access_code' => strtoupper(Str::random(12)),
                            'is_active' => true,
                        ]
                    );
                }

                if ($memberIndex % 3 === 0) {
                    StructurePet::query()->firstOrCreate(
                        ['client_id' => $client->id, 'structure_id' => $apartment->id, 'name' => "Mascota{$memberIndex}"],
                        [
                            'species' => PetSpecies::Dog,
                            'breed' => 'Mestizo',
                            'is_potentially_dangerous' => false,
                        ]
                    );
                }

                Vehicle::query()->firstOrCreate(
                    ['client_id' => $client->id, 'plate' => 'ABC'.str_pad((string) $memberIndex, 3, '0', STR_PAD_LEFT)],
                    [
                        'structure_id' => $apartment->id,
                        'brand' => 'Toyota',
                        'model' => 'Corolla',
                        'color' => 'Gris',
                        'type' => 'carro',
                        'is_visitor_vehicle' => false,
                    ]
                );
            }

            $host = StructureMember::query()->where('client_id', $client->id)->first();
            if ($host) {
                for ($k = 1; $k <= 5; $k++) {
                    VisitorPreAuthorization::query()->firstOrCreate(
                        [
                            'client_id' => $client->id,
                            'structure_id' => $host->structure_id,
                            'visitor_name' => "Visitante Piloto {$k}",
                            'valid_for_date' => now()->addDays($k)->toDateString(),
                        ],
                        [
                            'member_id' => $host->id,
                            'visitor_document' => sprintf('900%06d', $k),
                            'visitor_category' => VisitorCategory::Visitor,
                            'status' => AuthorizationStatus::Pending,
                            'qr_auth_token' => strtoupper(Str::random(16)),
                        ]
                    );
                }

                StructureAppUser::query()->firstOrCreate(
                    ['client_id' => $client->id, 'username' => 'admin.palmas'],
                    [
                        'member_id' => $host->id,
                        'email' => 'admin.palmas@'.$client->login_suffix,
                        'password' => 'AppUser123!',
                        'is_active' => true,
                    ]
                );
            }
        });
    }
}
