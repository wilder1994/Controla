<?php

declare(strict_types=1);

namespace App\Services\Ops;

use App\Enums\OperationalAlertType;
use App\Models\Client;
use App\Models\Installation;
use App\Models\ObservatoryReport;
use App\Models\OperationalAlert;
use App\Models\SupervisorPost;
use App\Models\User;

final class RecordOperationalAlertService
{
    public function panic(
        User $actor,
        int $companyId,
        ?int $clientId,
        ?int $installationId,
        ?float $lat,
        ?float $lng,
        string $note = '',
    ): OperationalAlert {
        $where = $this->placeLabel($clientId, $installationId);

        return $this->store(
            OperationalAlertType::Panic,
            $companyId,
            $actor->id,
            $clientId,
            $installationId,
            null,
            'Alerta de pánico',
            trim($actor->name.' activó pánico'.($where !== '' ? ' · '.$where : '').($note !== '' ? '. '.$note : '')),
            $lat,
            $lng,
        );
    }

    public function observatory(ObservatoryReport $report): OperationalAlert
    {
        $client = $report->client ?? Client::query()->find($report->client_id);
        $companyId = (int) ($client?->security_company_id ?? 0);
        $place = $report->installation?->name ?? 'sede';

        return $this->store(
            OperationalAlertType::Observatory,
            $companyId,
            $report->reported_by_user_id,
            (int) $report->client_id,
            (int) $report->installation_id,
            null,
            'Reporte Observatorio',
            'Nuevo reporte en '.$place,
            $report->latitude !== null ? (float) $report->latitude : null,
            $report->longitude !== null ? (float) $report->longitude : null,
            ['report_id' => $report->id, 'event_id' => $report->event_id],
        );
    }

    public function serviceChange(
        Client $client,
        string $body,
        ?Installation $installation = null,
        ?SupervisorPost $post = null,
        ?User $actor = null,
    ): OperationalAlert {
        return $this->store(
            OperationalAlertType::ServiceChange,
            (int) $client->security_company_id,
            $actor?->id,
            (int) $client->id,
            $installation?->id,
            $post?->id,
            'Novedad de servicio',
            $body,
        );
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function store(
        OperationalAlertType $type,
        int $companyId,
        ?int $actorId,
        ?int $clientId,
        ?int $installationId,
        ?int $postId,
        string $title,
        string $body,
        ?float $lat = null,
        ?float $lng = null,
        ?array $payload = null,
    ): OperationalAlert {
        $alert = OperationalAlert::query()->create([
            'type' => $type,
            'security_company_id' => $companyId,
            'actor_user_id' => $actorId,
            'client_id' => $clientId,
            'installation_id' => $installationId,
            'supervisor_post_id' => $postId,
            'title' => $title,
            'body' => $body,
            'latitude' => $lat,
            'longitude' => $lng,
            'payload' => $payload,
        ]);
        app(NotifyOpsSurface::class)->fromAlert($alert);

        return $alert;
    }

    private function placeLabel(?int $clientId, ?int $installationId): string
    {
        $parts = [];
        if ($installationId) {
            $name = Installation::query()->withoutGlobalScopes()->whereKey($installationId)->value('name');
            if (is_string($name) && $name !== '') {
                $parts[] = $name;
            }
        }
        if ($clientId) {
            $name = Client::query()->whereKey($clientId)->value('name');
            if (is_string($name) && $name !== '') {
                $parts[] = $name;
            }
        }

        return implode(' · ', $parts);
    }
}
