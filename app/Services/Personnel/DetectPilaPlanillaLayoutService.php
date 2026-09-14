<?php

declare(strict_types=1);

namespace App\Services\Personnel;

use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class DetectPilaPlanillaLayoutService
{
    private const TYPE_SCAN_COLS = 12;

    private const LABEL_SCAN_COLS = 90;

    private const MAX_ROW_HARD_CAP = 12000;

    public function detect(Spreadsheet $spreadsheet): PilaPlanillaLayout
    {
        $best = null;
        $bestHits = 0;

        foreach ($spreadsheet->getAllSheets() as $index => $sheet) {
            $layout = $this->detectSheet($sheet, $index);
            if ($layout === null) {
                continue;
            }

            $span = $layout->lastDataRow - $layout->firstDataRow + 1;
            if ($span > $bestHits) {
                $bestHits = $span;
                $best = $layout;
            }
        }

        if ($best === null) {
            throw new InvalidArgumentException('No se encontraron cédulas en la planilla. Revise que sea el Excel PILA (filas de cotizantes debajo del encabezado).');
        }

        return $best;
    }

    private function detectSheet(Worksheet $sheet, int $sheetIndex): ?PilaPlanillaLayout
    {
        $highestCol = min(Coordinate::columnIndexFromString($sheet->getHighestColumn()), self::LABEL_SCAN_COLS);
        $highestRow = min(
            max((int) $sheet->getHighestRow(), (int) $sheet->getHighestDataRow()),
            self::MAX_ROW_HARD_CAP,
        );
        if ($highestRow < 2) {
            return null;
        }

        /** @var array<string, list<int>> $clusters */
        $clusters = [];
        for ($row = 1; $row <= $highestRow; $row++) {
            $hit = $this->cotizanteHit($sheet, $row);
            if ($hit === null) {
                continue;
            }

            $key = $hit['typeCol'].':'.$hit['idCol'];
            $clusters[$key][] = $row;
        }

        if ($clusters === []) {
            return null;
        }

        $bestKey = '';
        $bestCount = 0;
        foreach ($clusters as $key => $rows) {
            if (count($rows) > $bestCount) {
                $bestCount = count($rows);
                $bestKey = $key;
            }
        }

        $rows = $clusters[$bestKey];
        [$typeCol, $idCol] = array_map('intval', explode(':', $bestKey));
        $firstDataRow = min($rows);
        $lastDataRow = max($rows);
        $headerRow = $this->titleRow($sheet, $firstDataRow, $highestCol) ?? max(1, $firstDataRow - 1);

        $nameCol = $this->nameColumn($sheet, $headerRow, $firstDataRow, $lastDataRow, $idCol, $highestCol);
        $totalCol = $this->labelColumn($sheet, $headerRow, 'total aportes', $highestCol) ?? 75;
        $aportanteRow = min($headerRow, 16);
        $valorCell = $this->valorCell($sheet, $aportanteRow, $highestCol);
        $pensionCol = $this->labelColumn($sheet, $aportanteRow, 'pension', $highestCol);
        $saludCol = $this->labelColumn($sheet, $aportanteRow, 'salud', $highestCol);

        return new PilaPlanillaLayout(
            sheetIndex: $sheetIndex,
            headerRow: $headerRow,
            firstDataRow: $firstDataRow,
            lastDataRow: $lastDataRow,
            maxCol: $highestCol,
            typeCol: $typeCol,
            idCol: $idCol,
            nameCol: $nameCol,
            totalCol: $totalCol,
            valorCell: $valorCell,
            pensionPeriod: $pensionCol !== null
                ? PilaPlanillaCells::periodFromCell(
                    $sheet,
                    Coordinate::stringFromColumnIndex($pensionCol).$this->periodRow($sheet, $pensionCol, $aportanteRow),
                )
                : PilaPlanillaCells::periodFromCell($sheet, 'B15'),
            saludPeriod: $saludCol !== null
                ? PilaPlanillaCells::periodFromCell(
                    $sheet,
                    Coordinate::stringFromColumnIndex($saludCol).$this->periodRow($sheet, $saludCol, $aportanteRow),
                )
                : PilaPlanillaCells::periodFromCell($sheet, 'H15'),
        );
    }

    /** @return array{typeCol: int, idCol: int}|null */
    private function cotizanteHit(Worksheet $sheet, int $row): ?array
    {
        for ($col = 1; $col <= self::TYPE_SCAN_COLS; $col++) {
            $type = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($col).$row)->getFormattedValue());
            if (! PilaPlanillaCells::isIdType($type)) {
                continue;
            }

            $idCol = $col + 1;
            $document = PilaPlanillaCells::cedula($sheet->getCell(Coordinate::stringFromColumnIndex($idCol).$row));
            if (PilaPlanillaCells::isDocumentNumber($document)) {
                return ['typeCol' => $col, 'idCol' => $idCol];
            }
        }

        return null;
    }

    private function titleRow(Worksheet $sheet, int $firstDataRow, int $highestCol): ?int
    {
        $limit = min(20, $highestCol);
        for ($row = $firstDataRow - 1; $row >= 1; $row--) {
            for ($col = 1; $col <= $limit; $col++) {
                $text = PilaPlanillaCells::text($sheet->getCell(Coordinate::stringFromColumnIndex($col).$row));
                if ($text === 'identificacion' || $text === 'nombre' || $text === 'no.') {
                    return $row;
                }
            }
        }

        return null;
    }

    private function nameColumn(Worksheet $sheet, int $headerRow, int $firstDataRow, int $lastDataRow, int $idCol, int $highestCol): int
    {
        $fromHeader = $this->labelColumn($sheet, $headerRow, 'nombre', min($highestCol, 20), $idCol + 1);
        if ($fromHeader !== null) {
            return $fromHeader;
        }

        $limit = min($lastDataRow, $firstDataRow + 4);
        $scores = [];
        for ($row = $firstDataRow; $row <= $limit; $row++) {
            for ($col = $idCol + 1; $col <= min($idCol + 8, $highestCol); $col++) {
                $text = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($col).$row)->getFormattedValue());
                if ($text === '' || is_numeric($text) || in_array(mb_strtoupper($text), ['X', 'SI', 'SÍ', 'NO'], true)) {
                    continue;
                }
                if (preg_match('/\p{L}{2,}/u', $text) !== 1) {
                    continue;
                }
                $scores[$col] = ($scores[$col] ?? 0) + 1;
            }
        }

        if ($scores !== []) {
            arsort($scores);

            return (int) array_key_first($scores);
        }

        return 9;
    }

    private function labelColumn(Worksheet $sheet, int $headerRow, string $needle, int $highestCol, int $fromCol = 1): ?int
    {
        $needle = PilaPlanillaCells::norm($needle);
        $found = null;
        for ($row = 1; $row <= $headerRow; $row++) {
            for ($col = $fromCol; $col <= $highestCol; $col++) {
                if (PilaPlanillaCells::text($sheet->getCell(Coordinate::stringFromColumnIndex($col).$row)) === $needle) {
                    $found = $col;
                }
            }
        }

        return $found;
    }

    private function valorCell(Worksheet $sheet, int $headerRow, int $highestCol): string
    {
        for ($row = 1; $row <= $headerRow; $row++) {
            if (PilaPlanillaCells::text($sheet->getCell('BJ'.$row)) === 'valor') {
                return 'BJ'.($row + 1);
            }
        }

        $last = $this->labelColumn($sheet, $headerRow, 'valor', $highestCol);
        if ($last !== null) {
            for ($row = $headerRow; $row >= 1; $row--) {
                if (PilaPlanillaCells::text($sheet->getCell(Coordinate::stringFromColumnIndex($last).$row)) === 'valor') {
                    return Coordinate::stringFromColumnIndex($last).($row + 1);
                }
            }
        }

        return 'BJ15';
    }

    private function periodRow(Worksheet $sheet, int $col, int $headerRow): int
    {
        $letter = Coordinate::stringFromColumnIndex($col);
        for ($row = 1; $row <= $headerRow; $row++) {
            if (PilaPlanillaCells::text($sheet->getCell($letter.$row)) === 'pension'
                || PilaPlanillaCells::text($sheet->getCell($letter.$row)) === 'salud') {
                return min($headerRow, $row + 1);
            }
        }

        return 15;
    }
}
