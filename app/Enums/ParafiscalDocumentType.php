<?php

namespace App\Enums;

use App\Models\Employee;

enum ParafiscalDocumentType: string
{
    case Planilla = 'planilla_seguridad_social';

    public function label(): string
    {
        return 'Planilla de seguridad social';
    }

    public function requirement(): DocumentRequirement
    {
        return DocumentRequirement::Optional;
    }

    public function isRepeatable(): bool
    {
        return true;
    }

    public function filenameSlug(): string
    {
        return 'Planilla_seguridad_social';
    }

    public function suggestedName(Employee $employee): string
    {
        return $this->filenameSlug().'_'.$employee->document_number;
    }
}
