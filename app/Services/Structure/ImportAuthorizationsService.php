<?php

declare(strict_types=1);

namespace App\Services\Structure;

use App\Domain\Structure\Data\CreateAuthorizationData;
use App\Enums\VisitorCategory;
use App\Models\Structure;
use App\Models\StructureMember;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

final class ImportAuthorizationsService implements ToCollection, WithHeadingRow
{
    private int $clientId;

    /** @var array<int, string> */
    private array $errors = [];

    private int $imported = 0;

    public function __construct(
        private readonly CreateAuthorizationService $createAuthorizationService,
    ) {}

    public function forClient(int $clientId): self
    {
        $this->clientId = $clientId;

        return $this;
    }

    public function collection(Collection $rows): void
    {
        DB::transaction(function () use ($rows): void {
            foreach ($rows as $index => $row) {
                $line = $index + 2;

                $visitorName = trim((string) ($row['visitante'] ?? $row['visitor_name'] ?? ''));
                $structureRef = trim((string) ($row['estructura'] ?? $row['structure'] ?? $row['apartamento'] ?? ''));
                $validDate = trim((string) ($row['fecha'] ?? $row['valid_for_date'] ?? ''));

                if ($visitorName === '' || $structureRef === '' || $validDate === '') {
                    $this->errors[] = "Fila {$line}: faltan campos obligatorios (visitante, estructura, fecha).";

                    continue;
                }

                $structure = Structure::query()
                    ->where('client_id', $this->clientId)
                    ->where(function ($q) use ($structureRef): void {
                        $q->where('code', $structureRef)
                            ->orWhere('name', $structureRef);
                    })
                    ->first();

                if ($structure === null) {
                    $this->errors[] = "Fila {$line}: estructura «{$structureRef}» no encontrada.";

                    continue;
                }

                $memberId = null;
                $hostDoc = trim((string) ($row['anfitrion_documento'] ?? $row['host_document'] ?? ''));
                if ($hostDoc !== '') {
                    $member = StructureMember::query()
                        ->where('client_id', $this->clientId)
                        ->where('document_number', $hostDoc)
                        ->first();
                    $memberId = $member?->id;
                }

                $categoryRaw = strtolower(trim((string) ($row['categoria'] ?? $row['category'] ?? 'visitor')));
                $category = match ($categoryRaw) {
                    'contratista', 'contractor' => VisitorCategory::Contractor,
                    'domicilio', 'delivery', 'mensajeria' => VisitorCategory::Delivery,
                    default => VisitorCategory::Visitor,
                };

                $this->createAuthorizationService->execute(new CreateAuthorizationData(
                    clientId: $this->clientId,
                    structureId: $structure->id,
                    memberId: $memberId,
                    visitorName: $visitorName,
                    visitorDocument: trim((string) ($row['documento'] ?? $row['visitor_document'] ?? '')) ?: null,
                    visitorCategory: $category,
                    validForDate: $validDate,
                    notes: trim((string) ($row['notas'] ?? $row['notes'] ?? '')) ?: null,
                ));

                $this->imported++;
            }
        });
    }

    public function importedCount(): int
    {
        return $this->imported;
    }

    /** @return array<int, string> */
    public function errors(): array
    {
        return $this->errors;
    }
}
