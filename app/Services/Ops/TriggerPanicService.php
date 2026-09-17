<?php

declare(strict_types=1);

namespace App\Services\Ops;

use App\Models\Client;
use App\Models\Installation;
use App\Models\Location;
use App\Models\OperationalAlert;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use InvalidArgumentException;

final class TriggerPanicService
{
    public function __construct(
        private readonly RecordOperationalAlertService $record,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(
        User $actor,
        ?float $lat,
        ?float $lng,
        string $note = '',
        ?int $clientId = null,
        ?int $installationId = null,
        array $payload = [],
    ): OperationalAlert {
        $companyId = (int) ($actor->security_company_id ?? 0);
        if ($companyId < 1 && $clientId) {
            $companyId = (int) Client::query()->whereKey($clientId)->value('security_company_id');
        }
        if ($companyId < 1) {
            throw new InvalidArgumentException('No hay empresa para registrar el pánico.');
        }

        if ($installationId) {
            $site = Installation::query()->withoutGlobalScopes()->find($installationId);
            if ($site !== null) {
                $clientId = $clientId ?: (int) $site->client_id;
            }
        }

        return $this->record->panic($actor, $companyId, $clientId, $installationId, $lat, $lng, $note, $payload);
    }

    public function fromPorteria(
        User $actor,
        Location $door,
        ?float $lat,
        ?float $lng,
        string $note = '',
    ): OperationalAlert {
        $door->loadMissing('installation');

        return $this->execute(
            $actor,
            $lat,
            $lng,
            $note,
            (int) $door->client_id ?: null,
            $door->installation_id ? (int) $door->installation_id : null,
            [
                'location_id' => $door->id,
                'location_name' => $door->name,
            ],
        );
    }

    public function fromClientPanel(User $actor, TenantContext $tenant, ?float $lat, ?float $lng, string $note = ''): OperationalAlert
    {
        $installationIds = $tenant->installationIds();
        $installationId = is_array($installationIds) && count($installationIds) === 1
            ? $installationIds[0]
            : null;

        return $this->execute(
            $actor,
            $lat,
            $lng,
            $note,
            (int) $tenant->clientId() ?: null,
            $installationId,
        );
    }
}
