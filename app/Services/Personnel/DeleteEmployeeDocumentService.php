<?php

declare(strict_types=1);

namespace App\Services\Personnel;

use App\Models\EmployeeDocument;
use App\Support\Files\StoredFileResponder;
use Illuminate\Support\Facades\File;
use RuntimeException;

final class DeleteEmployeeDocumentService
{
    public function execute(EmployeeDocument $document): void
    {
        if (! $document->hasFile()) {
            throw new RuntimeException('Este registro no tiene un PDF para eliminar.');
        }

        if (! $document->canDelete()) {
            throw new RuntimeException('Solo se puede eliminar durante las 12 horas siguientes a la carga.');
        }

        $absolute = StoredFileResponder::absolute((string) $document->disk_path);
        if (is_file($absolute)) {
            File::delete($absolute);
        }

        $document->delete();
    }
}
