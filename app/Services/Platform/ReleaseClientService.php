<?php

declare(strict_types=1);

namespace App\Services\Platform;

use App\Enums\ClientLifecycle;
use App\Enums\EvidenceEventType;
use App\Models\Client;
use Carbon\CarbonImmutable;

final class ReleaseClientService
{
    public function __construct(
        private readonly RecordLifecycleEvidenceService $evidenceService,
    ) {}

    public function execute(Client $client): Client
    {
        $now = CarbonImmutable::now();

        $client->update([
            'lifecycle' => ClientLifecycle::Released,
            'released_at' => $now,
            'is_active' => false,
        ]);

        $this->evidenceService->record(
            EvidenceEventType::ClientReleased,
            'Acta de retiro de conjunto',
            [
                'client_id' => $client->id,
                'client_name' => $client->name,
                'released_at' => $now->toIso8601String(),
            ],
            $client->security_company_id,
            $client->id,
            $now,
        );

        return $client->fresh();
    }
}
