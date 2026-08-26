<?php

declare(strict_types=1);

namespace App\Services\Company;

use App\Domain\Tenant\Data\CreateClientData;
use App\Enums\PartyType;
use App\Services\Tenant\CreateClientService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CommitClientImportService
{
    public function __construct(
        private readonly PreviewClientImportService $previewClientImportService,
        private readonly CreateClientService $createClientService,
    ) {}

    public function execute(int $companyId, int $userId): int
    {
        $preview = $this->previewClientImportService->get($companyId, $userId);
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
            foreach ($rows as $row) {
                $payload = $row['payload'] ?? null;
                if (! is_array($payload)) {
                    continue;
                }

                $this->createClientService->execute(new CreateClientData(
                    securityCompanyId: $companyId,
                    name: (string) $payload['name'],
                    partyType: PartyType::from((string) $payload['party_type']),
                    legalName: $payload['legal_name'] !== null ? (string) $payload['legal_name'] : null,
                    documentType: $payload['document_type'] !== null ? (string) $payload['document_type'] : null,
                    taxId: $payload['tax_id'] !== null ? (string) $payload['tax_id'] : null,
                    email: $payload['email'] !== null ? (string) $payload['email'] : null,
                    phone: $payload['phone'] !== null ? (string) $payload['phone'] : null,
                    representativeName: $payload['representative_name'] !== null ? (string) $payload['representative_name'] : null,
                    representativeEmail: $payload['representative_email'] !== null ? (string) $payload['representative_email'] : null,
                    structureTypeId: (int) $payload['structure_type_id'],
                    address: $payload['address'] !== null ? (string) $payload['address'] : null,
                    city: $payload['city'] !== null ? (string) $payload['city'] : null,
                    department: $payload['department'] !== null ? (string) $payload['department'] : null,
                    isActive: true,
                    hasAccess: (bool) $payload['has_access'],
                    hasSupervision: (bool) $payload['has_supervision'],
                ));
                $created++;
            }
        });

        $this->previewClientImportService->forget($companyId, $userId);

        return $created;
    }
}
