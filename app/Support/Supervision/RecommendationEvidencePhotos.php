<?php

declare(strict_types=1);

namespace App\Support\Supervision;

final class RecommendationEvidencePhotos
{
    /** @var list<string> */
    public const SLOTS = ['evidence_1', 'evidence_2', 'evidence_3'];

    /** @return list<array{key: string, label: string}> */
    public static function slots(): array
    {
        return [
            ['key' => 'evidence_1', 'label' => 'Evidencia 1'],
            ['key' => 'evidence_2', 'label' => 'Evidencia 2'],
            ['key' => 'evidence_3', 'label' => 'Evidencia 3'],
        ];
    }
}
