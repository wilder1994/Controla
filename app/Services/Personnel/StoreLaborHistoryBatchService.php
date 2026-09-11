<?php

declare(strict_types=1);

namespace App\Services\Personnel;

use App\Enums\DocumentFolder;
use App\Models\Employee;
use App\Models\EmployeeDocumentBatch;
use App\Support\Files\PdfPageReader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class StoreLaborHistoryBatchService
{
    public function execute(Employee $employee, UploadedFile $file, ?DocumentFolder $folder = null): EmployeeDocumentBatch
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'pdf');
        $relative = sprintf(
            'companies/%d/employees/%d/%s/batches/%s.%s',
            $employee->security_company_id,
            $employee->id,
            $folder?->value ?? 'lote',
            Str::uuid()->toString(),
            $extension,
        );

        $original = $file->getClientOriginalName() ?: basename($relative);
        $mime = $file->getMimeType() ?: 'application/pdf';

        $absolute = storage_path('app/'.$relative);
        File::ensureDirectoryExists(dirname($absolute));
        $file->move(dirname($absolute), basename($absolute));

        $pages = str_ends_with(strtolower($relative), '.pdf')
            ? PdfPageReader::count($absolute)
            : 1;

        return EmployeeDocumentBatch::query()->create([
            'security_company_id' => $employee->security_company_id,
            'employee_id' => $employee->id,
            'folder' => $folder,
            'original_name' => $original,
            'disk_path' => $relative,
            'mime' => $mime,
            'page_count' => $pages,
        ]);
    }
}
