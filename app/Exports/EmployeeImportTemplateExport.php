<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

final class EmployeeImportTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new EmployeeImportTemplateDataSheet,
            new EmployeeImportTemplateInstructionsSheet,
        ];
    }
}
