<?php

declare(strict_types=1);

namespace App\Services\Structure;

use App\Domain\Structure\Data\CreateAuthorizationData;
use App\Enums\AuthorizationStatus;
use App\Models\VisitorPreAuthorization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreateAuthorizationService
{
    public function execute(CreateAuthorizationData $data): VisitorPreAuthorization
    {
        return DB::transaction(function () use ($data): VisitorPreAuthorization {
            return VisitorPreAuthorization::query()->create([
                'client_id' => $data->clientId,
                'structure_id' => $data->structureId,
                'member_id' => $data->memberId,
                'visitor_name' => $data->visitorName,
                'visitor_document' => $data->visitorDocument,
                'visitor_category' => $data->visitorCategory,
                'valid_for_date' => $data->validForDate,
                'notes' => $data->notes,
                'status' => AuthorizationStatus::Pending,
                'qr_auth_token' => strtoupper(Str::random(16)),
            ]);
        });
    }
}
