<?php

declare(strict_types=1);

namespace App\Services\Personnel;

use App\Enums\AffiliationDocumentType;
use App\Enums\CertificateDocumentType;
use App\Enums\ContractingDocumentType;
use App\Enums\CourseDocumentType;
use App\Enums\DocumentFolder;
use App\Enums\LaborHistoryDocumentType;
use App\Enums\OtherDocumentType;
use App\Models\Employee;
use App\Models\EmployeeDocument;

final class MarkLaborHistoryNotApplicableService
{
    public function execute(
        Employee $employee,
        DocumentFolder $folder,
        LaborHistoryDocumentType|AffiliationDocumentType|CertificateDocumentType|ContractingDocumentType|CourseDocumentType|OtherDocumentType $type,
    ): EmployeeDocument {
        EmployeeDocument::query()
            ->where('employee_id', $employee->id)
            ->where('folder', $folder)
            ->where('document_type', $type->value)
            ->delete();

        return EmployeeDocument::query()->create([
            'security_company_id' => $employee->security_company_id,
            'employee_id' => $employee->id,
            'folder' => $folder,
            'document_type' => $type->value,
            'display_name' => $type->label(),
            'not_applicable' => true,
            'original_name' => $type->label(),
            'disk_path' => '',
            'mime' => null,
            'size_bytes' => 0,
        ]);
    }
}
