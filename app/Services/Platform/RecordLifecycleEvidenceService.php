<?php

declare(strict_types=1);

namespace App\Services\Platform;

use App\Enums\EvidenceEventType;
use App\Models\LifecycleEvidenceEvent;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

final class RecordLifecycleEvidenceService
{
    public function record(
        EvidenceEventType $type,
        string $title,
        array $payload,
        ?int $securityCompanyId = null,
        ?int $clientId = null,
        ?CarbonImmutable $at = null,
    ): LifecycleEvidenceEvent {
        $at ??= CarbonImmutable::now();
        $canonical = json_encode($payload, JSON_THROW_ON_ERROR);
        $hash = hash('sha256', $type->value.'|'.$title.'|'.$canonical.'|'.$at->toIso8601String());

        return LifecycleEvidenceEvent::query()->create([
            'security_company_id' => $securityCompanyId,
            'client_id' => $clientId,
            'event_type' => $type,
            'title' => $title,
            'payload' => $payload,
            'content_hash' => $hash,
            'occurred_at' => $at,
            'created_at' => $at,
        ]);
    }
}
