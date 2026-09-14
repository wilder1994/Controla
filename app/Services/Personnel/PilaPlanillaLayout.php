<?php

declare(strict_types=1);

namespace App\Services\Personnel;

final readonly class PilaPlanillaLayout
{
    public function __construct(
        public int $sheetIndex,
        public int $headerRow,
        public int $firstDataRow,
        public int $lastDataRow,
        public int $maxCol,
        public int $typeCol,
        public int $idCol,
        public int $nameCol,
        public int $totalCol,
        public string $valorCell,
        public ?string $pensionPeriod,
        public ?string $saludPeriod,
    ) {}
}
