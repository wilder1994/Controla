<?php

declare(strict_types=1);

namespace App\Services\Personnel;

use App\Enums\DocumentFolder;
use App\Enums\ParafiscalDocumentType;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Support\Files\StoredFileResponder;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;

final class CommitParafiscalPlanillaService
{
    public function __construct(
        private readonly PreviewParafiscalPlanillaService $preview,
        private readonly ParsePilaPlanillaService $parser,
        private readonly PilaPlanillaClipper $clipper,
    ) {}

    public function execute(int $companyId, int $userId): int
    {
        @set_time_limit(180);

        $cached = $this->preview->get($companyId, $userId);
        if ($cached === null || ! isset($cached['path'])) {
            throw ValidationException::withMessages([
                'file' => 'No hay una revisión vigente. Vuelve a cargar la planilla.',
            ]);
        }

        $absolute = storage_path('app/'.$cached['path']);
        if (! is_file($absolute)) {
            $this->preview->forget($companyId, $userId);
            throw ValidationException::withMessages([
                'file' => 'El Excel temporal ya no está. Vuelve a cargar la planilla.',
            ]);
        }

        $spreadsheet = IOFactory::load($absolute);
        $parsed = $this->parser->parseSpreadsheet($spreadsheet);
        $sheet = $spreadsheet->getSheet((int) $parsed['sheet_index']);

        $employees = Employee::query()
            ->where('security_company_id', $companyId)
            ->get(['id', 'security_company_id', 'document_number']);

        $byDocument = [];
        foreach ($employees as $employee) {
            $key = ParsePilaPlanillaService::normalizeCedula((string) $employee->document_number);
            if ($key !== '') {
                $byDocument[$key] = $employee;
            }
        }

        $pension = is_string($parsed['pension_period'] ?? null) ? $parsed['pension_period'] : null;
        $salud = is_string($parsed['salud_period'] ?? null) ? $parsed['salud_period'] : null;
        $takenOn = $pension !== null
            ? Carbon::createFromFormat('Y-m-d', $pension.'-01')->startOfMonth()->toDateString()
            : now()->startOfMonth()->toDateString();
        $display = $this->displayName($pension, $salud);
        $type = ParafiscalDocumentType::Planilla;
        $folder = DocumentFolder::Parafiscales;
        $count = 0;

        foreach ($parsed['groups'] as $document => $group) {
            $employee = $byDocument[$document] ?? null;
            if ($employee === null || $group['rows'] === []) {
                continue;
            }

            $relative = sprintf(
                'companies/%d/employees/%d/%s/%s-%s.xlsx',
                $employee->security_company_id,
                $employee->id,
                $folder->value,
                $type->value,
                Str::uuid()->toString(),
            );
            $dest = storage_path('app/'.$relative);
            File::ensureDirectoryExists(dirname($dest));

            $this->clipper->write(
                $sheet,
                $parsed['header_row'],
                $parsed['max_col'],
                $group['rows'],
                $parsed['valor_cell'],
                (float) $group['total'],
                $dest,
            );

            $this->replacePrevious($employee, $folder, $type, $takenOn);

            EmployeeDocument::query()->create([
                'security_company_id' => $employee->security_company_id,
                'employee_id' => $employee->id,
                'folder' => $folder,
                'document_type' => $type->value,
                'display_name' => $display,
                'page_from' => null,
                'page_to' => null,
                'pages' => null,
                'not_applicable' => false,
                'original_name' => $display,
                'disk_path' => $relative,
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'size_bytes' => is_file($dest) ? (int) filesize($dest) : 0,
                'taken_on' => $takenOn,
                'provider' => null,
            ]);
            $count++;
        }

        $spreadsheet->disconnectWorksheets();
        $this->preview->forget($companyId, $userId);

        return $count;
    }

    private function displayName(?string $pension, ?string $salud): string
    {
        if ($pension !== null && $salud !== null) {
            return 'Parafiscales pensión '.$pension.' · salud '.$salud.'.xlsx';
        }

        if ($pension !== null) {
            return 'Parafiscales pensión '.$pension.'.xlsx';
        }

        return 'Parafiscales '.now()->format('Y-m').'.xlsx';
    }

    private function replacePrevious(
        Employee $employee,
        DocumentFolder $folder,
        ParafiscalDocumentType $type,
        string $takenOn,
    ): void {
        $previous = EmployeeDocument::query()
            ->where('employee_id', $employee->id)
            ->where('folder', $folder)
            ->where('document_type', $type->value)
            ->whereDate('taken_on', $takenOn)
            ->get();

        foreach ($previous as $document) {
            if ($document->hasFile()) {
                $path = StoredFileResponder::absolute((string) $document->disk_path);
                if (is_file($path)) {
                    File::delete($path);
                }
            }
            $document->delete();
        }
    }
}
