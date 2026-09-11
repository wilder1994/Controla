<?php

namespace App\Enums;

enum DocumentRequirement: string
{
    case Required = 'required';
    case Optional = 'optional';
    case IfApplies = 'if_applies';

    public function label(): string
    {
        return match ($this) {
            self::Required => 'Obligatorio',
            self::Optional => 'Opcional',
            self::IfApplies => 'Si aplica',
        };
    }
}
