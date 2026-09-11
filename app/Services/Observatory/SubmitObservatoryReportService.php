<?php

declare(strict_types=1);

namespace App\Services\Observatory;

use App\Enums\InstallationKind;
use App\Enums\ObservatoryEventStatus;
use App\Enums\ObservatoryReportKind;
use App\Enums\ObservatoryReportSource;
use App\Models\Client;
use App\Models\Installation;
use App\Models\ObservatoryEvent;
use App\Models\ObservatoryReport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SubmitObservatoryReportService
{
    /**
     * @param  array{installation_id: int, kind: string, body: string, is_anonymous: bool, reporter_name?: ?string, reporter_phone?: ?string, photo?: ?UploadedFile, latitude?: ?float, longitude?: ?float}  $data
     */
    public function execute(Client $client, array $data, ?string $ip = null): ObservatoryReport
    {
        $installation = Installation::query()
            ->withoutGlobalScopes()
            ->where('client_id', $client->id)
            ->where('is_active', true)
            ->where('kind', InstallationKind::Colegio->value)
            ->whereKey((int) $data['installation_id'])
            ->first();

        if ($installation === null) {
            throw ValidationException::withMessages([
                'installation_id' => 'Elige un colegio de esta Secretaría.',
            ]);
        }

        $kind = ObservatoryReportKind::tryFrom((string) $data['kind']);
        if ($kind === null) {
            throw ValidationException::withMessages([
                'kind' => 'Indica el tipo de situación.',
            ]);
        }

        $body = trim((string) $data['body']);
        $anonymous = (bool) $data['is_anonymous'];
        $photo = $data['photo'] ?? null;
        $photoPath = $photo instanceof UploadedFile
            ? $photo->store('observatory/photos', 'public')
            : null;
        $coords = $this->coordinates($data, $installation);

        return DB::transaction(function () use ($client, $installation, $kind, $body, $anonymous, $data, $photoPath, $ip, $coords): ObservatoryReport {
            $event = $this->openOrAttach($client, $installation, $kind);

            return ObservatoryReport::query()->create([
                'event_id' => $event->id,
                'client_id' => $client->id,
                'installation_id' => $installation->id,
                'source' => ObservatoryReportSource::Comunidad,
                'kind' => $kind,
                'body' => $body,
                'is_anonymous' => $anonymous,
                'reporter_name' => $anonymous ? null : $this->nullable($data['reporter_name'] ?? null),
                'reporter_phone' => $anonymous ? null : $this->nullable($data['reporter_phone'] ?? null),
                'photo_path' => $photoPath,
                'latitude' => $coords['lat'],
                'longitude' => $coords['lng'],
                'ip_hash' => $ip !== null ? hash('sha256', $ip) : null,
            ]);
        });
    }

    private function openOrAttach(Client $client, Installation $installation, ObservatoryReportKind $kind): ObservatoryEvent
    {
        $minutes = max(1, (int) config('observatory.merge_window_minutes', 60));
        $since = now()->subMinutes($minutes);

        $existing = ObservatoryEvent::query()
            ->where('client_id', $client->id)
            ->where('installation_id', $installation->id)
            ->whereIn('status', [
                ObservatoryEventStatus::Nuevo->value,
                ObservatoryEventStatus::EnAtencion->value,
            ])
            ->whereHas('reports', function ($q) use ($kind, $since): void {
                $q->where('kind', $kind->value)
                    ->where('created_at', '>=', $since);
            })
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();

        if ($existing instanceof ObservatoryEvent) {
            return $existing;
        }

        return ObservatoryEvent::query()->create([
            'client_id' => $client->id,
            'installation_id' => $installation->id,
            'status' => ObservatoryEventStatus::Nuevo,
            'title' => $kind->label(),
            'opened_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{lat: ?string, lng: ?string}
     */
    private function coordinates(array $data, Installation $installation): array
    {
        $lat = $data['latitude'] ?? null;
        $lng = $data['longitude'] ?? null;
        if ($lat !== null && $lng !== null && $lat !== '' && $lng !== '') {
            return ['lat' => (string) $lat, 'lng' => (string) $lng];
        }

        if ($installation->hasCoordinates()) {
            return [
                'lat' => (string) $installation->latitude,
                'lng' => (string) $installation->longitude,
            ];
        }

        return ['lat' => null, 'lng' => null];
    }

    private function nullable(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));

        return $text !== '' ? $text : null;
    }
}
