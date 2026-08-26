<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

final class ClientImportTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new ClientImportTemplateDataSheet,
            new ClientImportTemplateInstructionsSheet,
        ];
    }
}
