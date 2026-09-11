<?php

namespace App\Enums;

use App\Models\Employee;
use App\Support\Personnel\OtherSupportNamer;

enum OtherDocumentType: string
{
    public const MAX = 20;

    case Otro = 'otro_soporte';

    public function label(): string
    {
        return 'Otro soporte';
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
        return 'Otro_soporte';
    }

    public function suggestedName(Employee $employee): string
    {
        return OtherSupportNamer::suggestedName($this->label(), $employee);
    }
}
