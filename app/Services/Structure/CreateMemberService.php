<?php

declare(strict_types=1);

namespace App\Services\Structure;

use App\Domain\Structure\Data\CreateMemberData;
use App\Models\StructureMember;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreateMemberService
{
    public function execute(CreateMemberData $data): StructureMember
    {
        return DB::transaction(function () use ($data): StructureMember {
            return StructureMember::query()->create([
                'client_id' => $data->clientId,
                'structure_id' => $data->structureId,
                'first_name' => $data->firstName,
                'last_name' => $data->lastName,
                'document_number' => $data->documentNumber,
                'member_type' => $data->memberType,
                'phone_primary' => $data->phonePrimary,
                'phone_secondary' => $data->phoneSecondary,
                'email' => $data->email,
                'has_app_access' => $data->hasAppAccess,
                'is_active' => $data->isActive,
                'access_code' => $this->generateAccessCode(),
            ]);
        });
    }

    private function generateAccessCode(): string
    {
        return strtoupper(Str::random(12));
    }
}
