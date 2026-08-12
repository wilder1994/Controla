<?php

declare(strict_types=1);

namespace App\Enums;

enum PlatformDocumentType: string
{
    case Contract = 'contract';
    case Invoice = 'invoice';
    case Act = 'act';
    case LegalCorpus = 'legal_corpus';
    case Attachment = 'attachment';

    public function label(): string
    {
        return match ($this) {
            self::Contract => 'Contrato',
            self::Invoice => 'Factura',
            self::Act => 'Acta',
            self::LegalCorpus => 'Normativa',
            self::Attachment => 'Adjunto',
        };
    }
}
