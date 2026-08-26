<?php

declare(strict_types=1);

namespace App\Services\Company;

use App\Domain\Employee\Data\SaveEmployeeData;
use App\Enums\BloodGroup;
use App\Enums\Sex;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CommitEmployeeImportService
{
    public function __construct(
        private readonly PreviewEmployeeImportService $previewEmployeeImportService,
        private readonly ManageCompanyJobTitleService $manageCompanyJobTitleService,
        private readonly ManageCompanyCollaboratorTypeService $manageCompanyCollaboratorTypeService,
        private readonly ManageEmployeeService $manageEmployeeService,
    ) {}

    public function execute(int $companyId, int $userId): int
    {
        $preview = $this->previewEmployeeImportService->get($companyId, $userId);
        if ($preview === null) {
            throw ValidationException::withMessages([
                'import' => 'No hay una revisión vigente. Vuelve a cargar el archivo.',
            ]);
        }

        $rows = $preview['rows'] ?? [];
        $hasRowError = (int) ($preview['error'] ?? 0) > 0;
        if (! $hasRowError) {
            foreach ($rows as $row) {
                if (($row['status'] ?? '') === 'error') {
                    $hasRowError = true;
                    break;
                }
            }
        }

        if ($hasRowError) {
            throw ValidationException::withMessages([
                'import' => 'Hay filas con error. Corrígelas antes de aceptar.',
            ]);
        }

        $created = 0;

        DB::transaction(function () use ($rows, $companyId, &$created): void {
            $titleIds = [];
            $typeIds = [];

            foreach ($rows as $row) {
                $payload = $row['payload'] ?? null;
                if (! is_array($payload)) {
                    continue;
                }

                $titleName = (string) $payload['job_title_name'];
                $titleKey = mb_strtolower($titleName);
                if (! isset($titleIds[$titleKey])) {
                    $titleIds[$titleKey] = $this->manageCompanyJobTitleService
                        ->findOrCreate($companyId, $titleName)
                        ->id;
                }

                $typeName = (string) $payload['collaborator_type_name'];
                $typeKey = mb_strtolower($typeName);
                if (! isset($typeIds[$typeKey])) {
                    $typeIds[$typeKey] = $this->manageCompanyCollaboratorTypeService
                        ->findOrCreate($companyId, $typeName)
                        ->id;
                }

                $this->manageEmployeeService->create(new SaveEmployeeData(
                    securityCompanyId: $companyId,
                    jobTitleId: $titleIds[$titleKey],
                    documentType: (string) $payload['document_type'],
                    documentNumber: (string) $payload['document_number'],
                    lastNamePaternal: (string) $payload['last_name_paternal'],
                    lastNameMaternal: (string) $payload['last_name_maternal'],
                    firstNames: (string) $payload['first_names'],
                    sex: Sex::from((string) $payload['sex']),
                    birthDate: (string) $payload['birth_date'],
                    collaboratorTypeId: $typeIds[$typeKey],
                    email: (string) $payload['email'],
                    nationality: (string) $payload['nationality'],
                    bloodGroup: BloodGroup::from((string) $payload['blood_group']),
                    birthDepartment: $payload['birth_department'] ?? null,
                    birthCity: $payload['birth_city'] ?? null,
                    emergencyPhone: $payload['emergency_phone'] ?? null,
                    emergencyContact: $payload['emergency_contact'] ?? null,
                    hasDisability: (bool) ($payload['has_disability'] ?? false),
                    documentIssueDepartment: $payload['document_issue_department'] ?? null,
                    documentIssueCity: $payload['document_issue_city'] ?? null,
                    documentIssuedAt: $payload['document_issued_at'] ?? null,
                    sameCostCenter: array_key_exists('same_cost_center', $payload)
                        ? ($payload['same_cost_center'] === null ? null : (bool) $payload['same_cost_center'])
                        : null,
                ));
                $created++;
            }
        });

        $this->previewEmployeeImportService->forget($companyId, $userId);

        return $created;
    }
}
