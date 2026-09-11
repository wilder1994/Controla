<?php

namespace App\Support\Personnel;

use App\Enums\AffiliationDocumentType;
use App\Enums\CertificateDocumentType;
use App\Enums\ContractingDocumentType;
use App\Enums\CourseDocumentType;
use App\Enums\DocumentFolder;
use App\Enums\LaborHistoryDocumentType;
use App\Enums\OtherDocumentType;
use InvalidArgumentException;

final class IndexedFolder
{
    public static function isIndexed(DocumentFolder $folder): bool
    {
        return $folder->isIndexed();
    }

    /** @return list<LaborHistoryDocumentType|AffiliationDocumentType|CertificateDocumentType|ContractingDocumentType|CourseDocumentType|OtherDocumentType> */
    public static function types(DocumentFolder $folder): array
    {
        return match ($folder) {
            DocumentFolder::HojaVida => LaborHistoryDocumentType::cases(),
            DocumentFolder::Contratacion => ContractingDocumentType::cases(),
            DocumentFolder::Afiliaciones => AffiliationDocumentType::cases(),
            DocumentFolder::Certificados => CertificateDocumentType::cases(),
            DocumentFolder::Cursos => CourseDocumentType::cases(),
            DocumentFolder::Otros => OtherDocumentType::cases(),
        };
    }

    public static function resolve(DocumentFolder $folder, string $value): LaborHistoryDocumentType|AffiliationDocumentType|CertificateDocumentType|ContractingDocumentType|CourseDocumentType|OtherDocumentType
    {
        $type = match ($folder) {
            DocumentFolder::HojaVida => LaborHistoryDocumentType::tryFrom($value),
            DocumentFolder::Contratacion => ContractingDocumentType::tryFrom($value),
            DocumentFolder::Afiliaciones => AffiliationDocumentType::tryFrom($value),
            DocumentFolder::Certificados => CertificateDocumentType::tryFrom($value),
            DocumentFolder::Cursos => CourseDocumentType::tryFrom($value),
            DocumentFolder::Otros => OtherDocumentType::tryFrom($value),
        };

        if ($type === null) {
            throw new InvalidArgumentException('Tipo de documento no válido para '.$folder->label().'.');
        }

        return $type;
    }
}
