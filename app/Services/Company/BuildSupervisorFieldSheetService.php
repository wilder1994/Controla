<?php

declare(strict_types=1);

namespace App\Services\Company;

use App\Domain\Supervision\Data\SupervisorFieldSheet;
use App\Enums\SupervisorAlarmKind;
use App\Enums\SupervisorAlarmResult;
use App\Enums\SupervisorFieldModule;
use App\Enums\SupervisorFieldSheetKind;
use App\Enums\SupervisorRiskImpact;
use App\Enums\SupervisorRiskLevel;
use App\Enums\SupervisorRiskLikelihood;
use App\Enums\SupervisorWeaponPermitKind;
use App\Models\SecurityCompany;
use App\Models\SupervisorAlarmType;
use App\Models\SupervisorControlBookType;
use App\Models\SupervisorDocumentType;
use App\Models\SupervisorFieldLog;
use App\Models\SupervisorRiskType;
use App\Models\SupervisorShiftReview;
use App\Models\SupervisorSupportType;
use App\Models\SupervisorWeaponBrand;
use App\Models\SupervisorWeaponType;
use App\Support\Supervision\RecommendationEvidencePhotos;
use App\Support\Supervision\SupervisorFieldSheetIntro;
use App\Support\Supervision\WeaponInspectionPhotos;
use Illuminate\Support\Facades\Storage;

final class BuildSupervisorFieldSheetService
{
    public function __construct(
        private readonly BuildSupervisorShiftSheetService $shiftSheets,
    ) {}

    public function forCompany(int $companyId, SupervisorFieldSheetKind $kind, int $id): ?SupervisorFieldSheet
    {
        return $this->build($companyId, $kind, $id, null);
    }

    public function forSupervisor(int $companyId, int $userId, SupervisorFieldSheetKind $kind, int $id): ?SupervisorFieldSheet
    {
        return $this->build($companyId, $kind, $id, $userId);
    }

    private function build(int $companyId, SupervisorFieldSheetKind $kind, int $id, ?int $userId): ?SupervisorFieldSheet
    {
        if ($kind === SupervisorFieldSheetKind::Shift) {
            return $userId === null
                ? $this->shiftSheets->forCompany($companyId, $id)
                : $this->shiftSheets->forSupervisor($companyId, $userId, $id);
        }

        if ($kind === SupervisorFieldSheetKind::Review) {
            $review = SupervisorShiftReview::query()
                ->whereKey($id)
                ->whereHas('shift', function ($query) use ($companyId, $userId): void {
                    $query->where('security_company_id', $companyId);
                    if ($userId !== null) {
                        $query->where('user_id', $userId);
                    }
                })
                ->with([
                    'client:id,name',
                    'supervisorPost:id,name,installation_id',
                    'supervisorPost.installation:id,name',
                    'employee:id,first_names,last_name_paternal,last_name_maternal',
                    'shift.user:id,name,username',
                    'shift.zone:id,name',
                    'shift.securityCompany',
                    'fieldLogs',
                ])
                ->first();

            return $review === null ? null : $this->fromReview($review);
        }

        $module = $kind->standaloneModule();
        if ($module === null) {
            return null;
        }

        $log = SupervisorFieldLog::query()
            ->whereKey($id)
            ->where('security_company_id', $companyId)
            ->where('module', $module)
            ->when($userId !== null, fn ($query) => $query->where('user_id', $userId))
            ->with(['client:id,name', 'user:id,name,username', 'shift.zone:id,name', 'shift.securityCompany', 'company'])
            ->first();

        return $log === null ? null : $this->fromLog($log);
    }

    private function fromReview(SupervisorShiftReview $review): SupervisorFieldSheet
    {
        $shift = $review->shift;
        $kind = SupervisorFieldSheetKind::Review;
        $recordedAt = $review->recorded_at ?? now();
        $sections = [];

        foreach ($review->fieldLogs as $log) {
            $sections[] = $this->sectionFromLog($log);
        }

        $company = $shift?->securityCompany;
        $brand = $this->branding($company);
        $guardName = $review->employee?->fullName();

        return new SupervisorFieldSheet(
            kind: $kind,
            id: (int) $review->id,
            folio: $kind->folio((int) $review->id, $recordedAt),
            companyName: $brand['display'],
            companyLegalName: $brand['legal'],
            companyTaxId: $brand['taxId'],
            companyLogoSrc: $brand['logo'],
            intro: SupervisorFieldSheetIntro::resolve($review->sheet_intro ?? $company?->field_sheet_intro),
            supervisorName: $shift?->user?->name ?? 'Supervisor',
            username: $shift?->user?->username,
            zoneName: $shift?->zone?->name,
            shiftLabel: $shift?->schedule_label,
            recordedAt: $recordedAt,
            clientName: $review->client?->name,
            installationName: $review->supervisorPost?->installation?->name,
            postName: $review->supervisorPost?->name,
            guardName: $guardName !== null && $guardName !== '' ? $guardName : null,
            hasNovelty: (bool) $review->has_novelty,
            notes: $review->notes,
            latitude: $review->latitude !== null ? (float) $review->latitude : null,
            longitude: $review->longitude !== null ? (float) $review->longitude : null,
            guardPhotoSrc: $this->dataUri($review->guard_photo_path),
            sections: $sections,
        );
    }

    private function fromLog(SupervisorFieldLog $log): SupervisorFieldSheet
    {
        $kind = SupervisorFieldSheetKind::fromStandaloneModule($log->module);
        if ($kind === null) {
            $kind = SupervisorFieldSheetKind::Document;
        }

        $recordedAt = $log->recorded_at ?? now();
        $shift = $log->shift;
        $company = $shift?->securityCompany ?? $log->company;
        $brand = $this->branding($company);

        return new SupervisorFieldSheet(
            kind: $kind,
            id: (int) $log->id,
            folio: $kind->folio((int) $log->id, $recordedAt),
            companyName: $brand['display'],
            companyLegalName: $brand['legal'],
            companyTaxId: $brand['taxId'],
            companyLogoSrc: $brand['logo'],
            intro: SupervisorFieldSheetIntro::resolve($company?->field_sheet_intro),
            supervisorName: $log->user?->name ?? $shift?->user?->name ?? 'Supervisor',
            username: $log->user?->username ?? $shift?->user?->username,
            zoneName: $shift?->zone?->name,
            shiftLabel: $shift?->schedule_label,
            recordedAt: $recordedAt,
            clientName: $log->client?->name,
            installationName: null,
            postName: null,
            guardName: null,
            hasNovelty: $log->outcome !== null && $log->outcome->value !== 'ok',
            notes: $log->notes,
            latitude: $log->latitude !== null ? (float) $log->latitude : null,
            longitude: $log->longitude !== null ? (float) $log->longitude : null,
            guardPhotoSrc: null,
            sections: [$this->sectionFromLog($log)],
        );
    }

    /**
     * @return array{title: string, rows: list<string>, photos: list<array{label: string, src: string}>}
     */
    private function sectionFromLog(SupervisorFieldLog $log): array
    {
        $payload = is_array($log->payload) ? $log->payload : [];

        return match ($log->module) {
            SupervisorFieldModule::Inventory => [
                'title' => 'Inventario',
                'rows' => $this->inventoryRows($payload),
                'photos' => [],
            ],
            SupervisorFieldModule::ControlBooks => [
                'title' => 'Libros de control',
                'rows' => $this->controlBookRows($payload, (int) $log->security_company_id),
                'photos' => [],
            ],
            SupervisorFieldModule::Folders => [
                'title' => 'Carpetas',
                'rows' => $this->folderRows($payload),
                'photos' => [],
            ],
            SupervisorFieldModule::Weapons => [
                'title' => 'Armamento',
                'rows' => $this->weaponRows($payload, (int) $log->security_company_id),
                'photos' => $this->weaponPhotos($payload),
            ],
            SupervisorFieldModule::Recommendations => [
                'title' => 'Recomendaciones',
                'rows' => $this->recommendationRows($payload, (int) $log->security_company_id),
                'photos' => $this->recommendationPhotos($payload),
            ],
            SupervisorFieldModule::Alarms => [
                'title' => 'Alarma',
                'rows' => $this->alarmRows($payload, (int) $log->security_company_id),
                'photos' => [],
            ],
            SupervisorFieldModule::Supports => [
                'title' => 'Apoyo',
                'rows' => $this->supportRows($payload, (int) $log->security_company_id),
                'photos' => [],
            ],
            SupervisorFieldModule::Documents => [
                'title' => 'Documentos',
                'rows' => $this->documentRows($payload, (int) $log->security_company_id),
                'photos' => [],
            ],
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    private function inventoryRows(array $payload): array
    {
        $rows = [];
        foreach ($payload['items'] ?? [] as $item) {
            if (! is_array($item)) {
                continue;
            }
            $status = ($item['status'] ?? '') === 'bad' ? 'Malo' : 'Bueno';
            $line = trim((string) ($item['type'] ?? 'Elemento')).' · '.$status;
            if (! empty($item['notes'])) {
                $line .= ' — '.$item['notes'];
            }
            $rows[] = $line;
        }

        return $rows !== [] ? $rows : ['Sin elementos.'];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    private function controlBookRows(array $payload, int $companyId): array
    {
        $ids = collect($payload['items'] ?? [])->pluck('control_book_type_id')->filter()->unique()->all();
        $names = $ids === []
            ? []
            : SupervisorControlBookType::query()->where('security_company_id', $companyId)->whereKey($ids)->pluck('name', 'id')->all();

        $rows = [];
        foreach ($payload['items'] ?? [] as $item) {
            if (! is_array($item)) {
                continue;
            }
            $name = $item['control_book_type'] ?? $names[(int) ($item['control_book_type_id'] ?? 0)] ?? 'Libro';
            $novelty = ($item['novelty'] ?? '') === 'yes' ? 'Con novedad' : 'Sin novedad';
            $line = $name.' · '.$novelty;
            if (! empty($item['notes'])) {
                $line .= ' — '.$item['notes'];
            }
            $rows[] = $line;
        }

        return $rows !== [] ? $rows : ['Sin libros.'];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    private function folderRows(array $payload): array
    {
        $status = ($payload['status'] ?? '') === 'missing' ? 'Con faltantes' : 'Completa';
        $rows = [$status];
        if (! empty($payload['missing_items'])) {
            $rows[] = (string) $payload['missing_items'];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    private function weaponRows(array $payload, int $companyId): array
    {
        $type = SupervisorWeaponType::query()
            ->where('security_company_id', $companyId)
            ->whereKey((int) ($payload['weapon_type_id'] ?? 0))
            ->value('name');
        $brand = SupervisorWeaponBrand::query()
            ->where('security_company_id', $companyId)
            ->whereKey((int) ($payload['weapon_brand_id'] ?? 0))
            ->value('name');
        $permit = SupervisorWeaponPermitKind::tryFrom((string) ($payload['permit_kind'] ?? ''));

        $rows = [
            'Tipo: '.($type ?? '—'),
            'Marca: '.($brand ?? '—'),
            'Serial: '.($payload['serial'] ?? '—'),
            'Calibre: '.($payload['caliber'] ?? '—'),
            'Permiso: '.($permit?->label() ?? '—').' '.($payload['permit_number'] ?? ''),
            'Vence: '.($payload['permit_expires_at'] ?? '—'),
            'Munición: '.($payload['ammo_quantity'] ?? '—').' · '.($payload['ammo_caliber'] ?? ''),
            'Novedad: '.(($payload['novelty'] ?? '') === 'yes' ? 'Sí' : 'No'),
            'Aseo: '.(($payload['cleaned'] ?? '') === 'yes' ? 'Sí' : 'No'),
        ];
        if (! empty($payload['notes'])) {
            $rows[] = (string) $payload['notes'];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{label: string, src: string}>
     */
    private function weaponPhotos(array $payload): array
    {
        $photos = is_array($payload['photos'] ?? null) ? $payload['photos'] : [];
        $out = [];
        foreach (WeaponInspectionPhotos::slots() as $slot) {
            $src = $this->dataUri(isset($photos[$slot['key']]) ? (string) $photos[$slot['key']] : null);
            if ($src !== null) {
                $out[] = ['label' => $slot['label'], 'src' => $src];
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    private function recommendationRows(array $payload, int $companyId): array
    {
        $ids = collect($payload['items'] ?? [])->pluck('risk_type_id')->filter()->unique()->all();
        $names = $ids === []
            ? []
            : SupervisorRiskType::query()->where('security_company_id', $companyId)->whereKey($ids)->pluck('name', 'id')->all();

        $rows = [];
        foreach ($payload['items'] ?? [] as $index => $item) {
            if (! is_array($item)) {
                continue;
            }
            $n = $index + 1;
            $type = $item['risk_type'] ?? $names[(int) ($item['risk_type_id'] ?? 0)] ?? 'Riesgo';
            $level = SupervisorRiskLevel::tryFrom((string) ($item['risk_level'] ?? ''));
            $likelihood = SupervisorRiskLikelihood::tryFrom((string) ($item['likelihood'] ?? ''));
            $impact = SupervisorRiskImpact::tryFrom((string) ($item['impact'] ?? ''));
            $rows[] = $n.'. '.$type.' · '.($level?->label() ?? '');
            $rows[] = 'Riesgo: '.($item['risk'] ?? '');
            $rows[] = 'P: '.($likelihood?->label() ?? '').' · I: '.($impact?->label() ?? '');
            $rows[] = 'Consecuencia: '.($item['consequence'] ?? '');
            $rows[] = 'Tratamiento: '.($item['treatment'] ?? '');
        }

        return $rows !== [] ? $rows : ['Sin recomendaciones.'];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{label: string, src: string}>
     */
    private function recommendationPhotos(array $payload): array
    {
        $out = [];
        foreach ($payload['items'] ?? [] as $index => $item) {
            if (! is_array($item)) {
                continue;
            }
            $photos = is_array($item['photos'] ?? null) ? $item['photos'] : [];
            foreach (RecommendationEvidencePhotos::slots() as $slot) {
                $src = $this->dataUri(isset($photos[$slot['key']]) ? (string) $photos[$slot['key']] : null);
                if ($src !== null) {
                    $out[] = ['label' => 'Riesgo '.($index + 1).' · '.$slot['label'], 'src' => $src];
                }
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    private function alarmRows(array $payload, int $companyId): array
    {
        $type = SupervisorAlarmType::query()
            ->where('security_company_id', $companyId)
            ->whereKey((int) ($payload['alarm_type_id'] ?? 0))
            ->value('name');
        $kind = SupervisorAlarmKind::tryFrom((string) ($payload['kind'] ?? ''));
        $result = SupervisorAlarmResult::tryFrom((string) ($payload['result'] ?? ''));

        return [
            'Tipo: '.($type ?? '—'),
            'Modalidad: '.($kind?->label() ?? '—'),
            'Resultado: '.($result?->label() ?? '—'),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    private function supportRows(array $payload, int $companyId): array
    {
        $type = SupervisorSupportType::query()
            ->where('security_company_id', $companyId)
            ->whereKey((int) ($payload['support_type_id'] ?? 0))
            ->value('name');

        $rows = ['Tipo: '.($type ?? '—')];
        if (! empty($payload['reason'])) {
            $rows[] = 'Motivo: '.$payload['reason'];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    private function documentRows(array $payload, int $companyId): array
    {
        $ids = collect($payload['items'] ?? [])->pluck('document_type_id')->filter()->unique()->all();
        $names = $ids === []
            ? []
            : SupervisorDocumentType::query()->where('security_company_id', $companyId)->whereKey($ids)->pluck('name', 'id')->all();

        $rows = [];
        foreach ($payload['items'] ?? [] as $item) {
            if (! is_array($item)) {
                continue;
            }
            $name = $item['document_type'] ?? $names[(int) ($item['document_type_id'] ?? 0)] ?? 'Documento';
            $line = $name.' · Entregados: '.((int) ($item['delivered'] ?? 0)).' · Pendientes: '.((int) ($item['pending'] ?? 0));
            if (! empty($item['notes'])) {
                $line .= ' — '.$item['notes'];
            }
            $rows[] = $line;
        }

        return $rows !== [] ? $rows : ['Sin documentos.'];
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
