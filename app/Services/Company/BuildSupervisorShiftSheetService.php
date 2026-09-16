<?php

declare(strict_types=1);

namespace App\Services\Company;

use App\Domain\Supervision\Data\SupervisorFieldSheet;
use App\Enums\SupervisorChecklistKind;
use App\Enums\SupervisorFieldModule;
use App\Enums\SupervisorFieldOutcome;
use App\Enums\SupervisorFieldSheetKind;
use App\Enums\SupervisorShiftStatus;
use App\Models\SecurityCompany;
use App\Models\SupervisorChecklistItem;
use App\Models\SupervisorFieldLog;
use App\Models\SupervisorShift;
use App\Models\SupervisorShiftReview;
use App\Support\Supervision\SupervisorFieldSheetIntro;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;

final class BuildSupervisorShiftSheetService
{
    public function forCompany(int $companyId, int $shiftId): ?SupervisorFieldSheet
    {
        return $this->build($companyId, $shiftId, null);
    }

    public function forSupervisor(int $companyId, int $userId, int $shiftId): ?SupervisorFieldSheet
    {
        return $this->build($companyId, $shiftId, $userId);
    }

    public function freeze(SupervisorShift $shift): void
    {
        $shift->loadMissing([
            'user',
            'zone',
            'shiftTemplate',
            'fleetVehicle',
            'securityCompany',
            'reviews.client',
            'fieldLogs.client',
            'locations',
        ]);

        $shift->update(['sheet_snapshot' => $this->snapshotArray($shift)]);
    }

    private function build(int $companyId, int $shiftId, ?int $userId): ?SupervisorFieldSheet
    {
        $shift = SupervisorShift::query()
            ->whereKey($shiftId)
            ->where('security_company_id', $companyId)
            ->when($userId !== null, fn ($query) => $query->where('user_id', $userId))
            ->with([
                'user',
                'zone',
                'shiftTemplate',
                'fleetVehicle',
                'securityCompany',
                'reviews.client',
                'fieldLogs.client',
                'locations' => fn ($q) => $q->orderBy('recorded_at'),
            ])
            ->first();

        if ($shift === null) {
            return null;
        }

        if ($shift->status === SupervisorShiftStatus::Closed && is_array($shift->sheet_snapshot) && $shift->sheet_snapshot !== []) {
            return $this->fromSnapshot($shift);
        }

        return $this->fromShift($shift);
    }

    private function fromShift(SupervisorShift $shift): SupervisorFieldSheet
    {
        $payload = $this->snapshotArray($shift);

        return $this->sheetFromPayload($shift, $payload, $shift->isOpen());
    }

    private function fromSnapshot(SupervisorShift $shift): SupervisorFieldSheet
    {
        return $this->sheetFromPayload($shift, $shift->sheet_snapshot ?? [], false);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function sheetFromPayload(SupervisorShift $shift, array $payload, bool $draft): SupervisorFieldSheet
    {
        $kind = SupervisorFieldSheetKind::Shift;
        $recordedAt = isset($payload['recorded_at'])
            ? CarbonImmutable::parse((string) $payload['recorded_at'])
            : ($shift->started_at ?? now());
        $company = $shift->securityCompany;
        $brand = $this->branding($company);
        $lat = isset($payload['latitude']) ? (float) $payload['latitude'] : null;
        $lng = isset($payload['longitude']) ? (float) $payload['longitude'] : null;

        $sections = [];
        foreach ($payload['sections'] ?? [] as $section) {
            if (! is_array($section)) {
                continue;
            }
            $photos = [];
            foreach ($section['photos'] ?? [] as $photo) {
                if (! is_array($photo)) {
                    continue;
                }
                $src = $this->dataUri(isset($photo['path']) ? (string) $photo['path'] : null);
                if ($src !== null) {
                    $photos[] = ['label' => (string) ($photo['label'] ?? 'Foto'), 'src' => $src];
                }
            }
            $sections[] = [
                'title' => (string) ($section['title'] ?? ''),
                'rows' => array_values(array_map('strval', $section['rows'] ?? [])),
                'photos' => $photos,
            ];
        }

        return new SupervisorFieldSheet(
            kind: $kind,
            id: (int) $shift->id,
            folio: (string) ($payload['folio'] ?? $kind->folio((int) $shift->id, $recordedAt)),
            companyName: $brand['display'],
            companyLegalName: $brand['legal'],
            companyTaxId: $brand['taxId'],
            companyLogoSrc: $brand['logo'],
            intro: (string) ($payload['intro'] ?? SupervisorFieldSheetIntro::resolve($company?->field_sheet_intro)),
            supervisorName: (string) ($payload['supervisor_name'] ?? $shift->user?->name ?? 'Supervisor'),
            username: $payload['username'] ?? $shift->user?->username,
            zoneName: $payload['zone_name'] ?? $shift->zone?->name,
            shiftLabel: $payload['shift_label'] ?? $shift->schedule_label,
            recordedAt: $recordedAt,
            clientName: null,
            installationName: null,
            postName: null,
            guardName: null,
            hasNovelty: (bool) ($payload['has_novelty'] ?? false),
            notes: isset($payload['notes']) ? (string) $payload['notes'] : $shift->notes,
            latitude: $lat,
            longitude: $lng,
            guardPhotoSrc: $this->dataUri(isset($payload['selfie_path']) ? (string) $payload['selfie_path'] : $shift->km_start_selfie_path),
            sections: $sections,
            isDraft: $draft,
            mapImageSrc: $this->staticMapSrc($lat, $lng),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshotArray(SupervisorShift $shift): array
    {
        $started = $shift->started_at ?? now();
        $kind = SupervisorFieldSheetKind::Shift;
        $first = $shift->locations->sortBy('recorded_at')->first();
        $last = $shift->locations->sortByDesc('recorded_at')->first();
        $lat = $first?->latitude !== null ? (float) $first->latitude : ($last?->latitude !== null ? (float) $last->latitude : null);
        $lng = $first?->longitude !== null ? (float) $first->longitude : ($last?->longitude !== null ? (float) $last->longitude : null);
        $reviews = $shift->reviews;
        $logs = $shift->fieldLogs;
        $hasNovelty = $reviews->contains(fn (SupervisorShiftReview $review) => $review->has_novelty)
            || $logs->contains(fn (SupervisorFieldLog $log) => $log->outcome !== null && $log->outcome !== SupervisorFieldOutcome::Ok);

        $activity = [];
        foreach ($reviews as $review) {
            $at = $review->recorded_at;
            $line = ($at?->timezone(config('app.timezone'))->format('d/m H:i') ?? '—')
                .' · Revista'.($review->client?->name ? ' · '.$review->client->name : '')
                .($review->has_novelty ? ' · novedad' : '');
            $activity[] = $line;
        }
        foreach ($logs as $log) {
            if (! in_array($log->module, [SupervisorFieldModule::Alarms, SupervisorFieldModule::Supports, SupervisorFieldModule::Documents], true)) {
                continue;
            }
            $at = $log->recorded_at;
            $activity[] = ($at?->timezone(config('app.timezone'))->format('d/m H:i') ?? '—')
                .' · '.$log->module->label()
                .($log->client?->name ? ' · '.$log->client->name : '');
        }

        $ppe = $this->checklistRows((int) $shift->security_company_id, SupervisorChecklistKind::Ppe, is_array($shift->ppe_checklist) ? $shift->ppe_checklist : []);
        $vehicle = $this->checklistRows((int) $shift->security_company_id, SupervisorChecklistKind::Vehicle, is_array($shift->vehicle_checklist) ? $shift->vehicle_checklist : []);

        $startPhotos = array_values(array_filter([
            $this->photoRef('Selfie de inicio', $shift->km_start_selfie_path),
            $this->photoRef('Odómetro de inicio', $shift->km_start_photo_path),
        ]));
        $endPhotos = array_values(array_filter([
            $this->photoRef('Selfie de cierre', $shift->km_end_selfie_path),
            $this->photoRef('Odómetro de cierre', $shift->km_end_photo_path),
        ]));

        $endRows = [
            'Fin: '.($shift->ended_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? ($shift->isOpen() ? 'Turno abierto' : '—')),
            'Km cierre: '.($shift->km_end ?? '—'),
            'Km recorridos: '.($shift->km_traveled ?? '—'),
        ];
        if ($shift->closed_by_system) {
            $endRows[] = 'Cierre por el sistema (sin fotos de cierre).';
        }

        $gpsRows = [
            'Inicio GPS: '.($first !== null ? number_format((float) $first->latitude, 6).', '.number_format((float) $first->longitude, 6) : 'Sin GPS aún'),
        ];
        if ($last !== null && ($last->id !== $first?->id)) {
            $gpsRows[] = 'Último GPS: '.number_format((float) $last->latitude, 6).', '.number_format((float) $last->longitude, 6);
        }

        return [
            'folio' => $kind->folio((int) $shift->id, $started),
            'recorded_at' => $started->toIso8601String(),
            'intro' => SupervisorFieldSheetIntro::resolve($shift->securityCompany?->field_sheet_intro),
            'supervisor_name' => $shift->user?->name ?? 'Supervisor',
            'username' => $shift->user?->username,
            'zone_name' => $shift->zone?->name ?? $shift->route_zone,
            'shift_label' => $shift->schedule_label ?? $shift->shiftTemplate?->scheduleLabel(),
            'latitude' => $lat,
            'longitude' => $lng,
            'has_novelty' => $hasNovelty,
            'notes' => $shift->notes,
            'selfie_path' => $shift->km_start_selfie_path,
            'sections' => [
                [
                    'title' => 'Inicio de turno',
                    'rows' => [
                        'Inicio: '.$started->timezone(config('app.timezone'))->format('d/m/Y H:i'),
                        'Vehículo: '.($shift->fleetVehicle?->plate ?? '—'),
                        'Km inicio: '.($shift->km_start ?? '—'),
                        ...$gpsRows,
                    ],
                    'photos' => $startPhotos,
                ],
                [
                    'title' => 'Preoperacional',
                    'rows' => array_merge(
                        $ppe !== [] ? array_merge(['EPP:'], $ppe) : ['EPP: sin registro'],
                        $vehicle !== [] ? array_merge(['Vehículo:'], $vehicle) : ['Vehículo: sin registro'],
                    ),
                    'photos' => [],
                ],
                [
                    'title' => 'Actividad del turno',
                    'rows' => $activity !== [] ? $activity : ['Sin revistas, alarmas ni apoyos aún.'],
                    'photos' => [],
                ],
                [
                    'title' => 'Cierre de turno',
                    'rows' => $endRows,
                    'photos' => $endPhotos,
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    private function checklistRows(int $companyId, SupervisorChecklistKind $kind, array $payload): array
    {
        $labels = SupervisorChecklistItem::keyedLabels($companyId, $kind);
        $rows = [];
        foreach ($labels as $key => $label) {
            $ok = (bool) ($payload[$key] ?? false);
            $rows[] = $label.': '.($ok ? 'Sí' : 'No');
        }

        return $rows;
    }

    /** @return array{label: string, path: string}|null */
    private function photoRef(string $label, ?string $path): ?array
    {
        if ($path === null || $path === '') {
            return null;
        }

        return ['label' => $label, 'path' => $path];
    }

    private function staticMapSrc(?float $lat, ?float $lng): ?string
    {
        if ($lat === null || $lng === null) {
            return null;
        }
        $key = (string) config('google-maps.server_api_key');
        if ($key === '') {
            return null;
        }

        return 'https://maps.googleapis.com/maps/api/staticmap?'.http_build_query([
            'center' => $lat.','.$lng,
            'zoom' => 16,
            'size' => '640x280',
            'maptype' => 'satellite',
            'markers' => 'color:0x22c55e|'.$lat.','.$lng,
            'key' => $key,
        ]);
    }

    /**
     * @return array{display: string, legal: string, taxId: ?string, logo: ?string}
     */
    private function branding(?SecurityCompany $company): array
    {
        $display = $company?->displayName() ?? 'Empresa';

        return [
            'display' => $display,
            'legal' => (string) ($company?->legal_name ?: $display),
            'taxId' => $company?->tax_id,
            'logo' => $this->dataUri($company?->logo_path),
        ];
    }

    private function dataUri(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }
        if (! Storage::disk('local')->exists($path)) {
            return null;
        }
        $bytes = Storage::disk('local')->get($path);
        if ($bytes === null || $bytes === '') {
            return null;
        }
        $mime = Storage::disk('local')->mimeType($path) ?: 'image/jpeg';

        return 'data:'.$mime.';base64,'.base64_encode($bytes);
    }
}
