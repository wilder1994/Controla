<?php

declare(strict_types=1);

namespace App\Enums;

enum LegalCorpusType: string
{
    case Contract = 'contract';
    case Terms = 'terms';
    case PrivacyPolicy = 'privacy_policy';
    case ProcedureLifecycle = 'procedure_lifecycle';

    public function label(): string
    {
        return match ($this) {
            self::Contract => 'Contrato de licencia SaaS',
            self::Terms => 'Términos y condiciones',
            self::PrivacyPolicy => 'Política de tratamiento de datos',
            self::ProcedureLifecycle => 'Procedimiento suspensión y archivo',
        };
    }
}
