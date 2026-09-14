<?php

declare(strict_types=1);

namespace App\Services\Personnel;

use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class ParsePilaPlanillaService
{
    public const MAX_DATA_ROWS = 8000;

    /**
     * @return array{
     *     header_row: int,
     *     max_col: int,
     *     id_col: int,
     *     name_col: int,
     *     total_col: int,
     *     valor_cell: string,
     *     pension_period: ?string,
     *     salud_period: ?string,
     *     groups: array<string, array{document: string, name: string, rows: list<int>, total: float}>
     * }
     */
    public function parsePath(string $absolutePath): array
    {
        if (! is_file($absolutePath)) {
            throw new InvalidArgumentException('No se encontró el Excel de la planilla.');
        }

        $spreadsheet = IOFactory::load($absolutePath);

        return $this->parseSpreadsheet($spreadsheet);
    }

    /**
     * @return array{
     *     header_row: int,
     *     max_col: int,
     *     id_col: int,
     *     name_col: int,
     *     total_col: int,
     *     valor_cell: string,
     *     pension_period: ?string,
     *     salud_period: ?string,
     *     groups: array<string, array{document: string, name: string, rows: list<int>, total: float}>
     * }
     */
    public function parseSpreadsheet(Spreadsheet $spreadsheet): array
    {
        $sheet = $spreadsheet->getSheet(0);
        $highestCol = min(Coordinate::columnIndexFromString($sheet->getHighestColumn()), 90);
        $scanRows = min((int) $sheet->getHighestRow(), 30);

        $idHeader = $this->findLabel($sheet, 'identificacion', $scanRows, $highestCol);
        $headerRow = $idHeader[0] ?? $this->findLabel($sheet, 'no.', $scanRows, min($highestCol, 10))[0] ?? 20;
        $idTypeCol = $idHeader[1] ?? 4;
        $idCol = $idTypeCol + 1;

        $nameCol = $this->findLabelInRow($sheet, 'nombre', $headerRow, $highestCol)[1]
            ?? $this->findLabel($sheet, 'nombre', $scanRows, $highestCol)[1]
            ?? 9;

        $totalCol = $this->findLabelInRow($sheet, 'total aportes', $headerRow, $highestCol)[1]
            ?? $this->findLabel($sheet, 'total aportes', $scanRows, $highestCol)[1]
            ?? 75;

        $valorHit = $this->findLabel($sheet, 'valor', $scanRows, $highestCol);
        $valorCell = $valorHit !== null
            ? Coordinate::stringFromColumnIndex($valorHit[1]).($valorHit[0] + 1)
            : 'BJ15';

        $pensionHit = $this->findLabel($sheet, 'pension', $scanRows, $highestCol);
        $saludHit = $this->findLabel($sheet, 'salud', $scanRows, $highestCol);
        $pensionPeriod = $pensionHit !== null
            ? $this->periodFromCell($sheet, Coordinate::stringFromColumnIndex($pensionHit[1]).($pensionHit[0] + 1))
            : $this->periodFromCell($sheet, 'B15');
        $saludPeriod = $saludHit !== null
            ? $this->periodFromCell($sheet, Coordinate::stringFromColumnIndex($saludHit[1]).($saludHit[0] + 1))
            : $this->periodFromCell($sheet, 'H15');

        $highestRow = (int) $sheet->getHighestDataRow();
        $groups = [];
        $emptyStreak = 0;
        $dataCount = 0;

        for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
            $document = $this->cellCedula($sheet->getCell(Coordinate::stringFromColumnIndex($idCol).$row));
            if ($document === '') {
                $emptyStreak++;
                if ($emptyStreak >= 40) {
                    break;
                }

                continue;
            }

            $emptyStreak = 0;
            $dataCount++;
            if ($dataCount > self::MAX_DATA_ROWS) {
                throw new InvalidArgumentException('La planilla supera el máximo de '.self::MAX_DATA_ROWS.' filas.');
            }

            $name = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($nameCol).$row)->getFormattedValue());
            $total = $this->cellAmount($sheet->getCell(Coordinate::stringFromColumnIndex($totalCol).$row));

            if (! isset($groups[$document])) {
                $groups[$document] = [
                    'document' => $document,
                    'name' => $name,
                    'rows' => [],
                    'total' => 0.0,
                ];
            }

            $groups[$document]['rows'][] = $row;
            $groups[$document]['total'] += $total;
            if ($groups[$document]['name'] === '' && $name !== '') {
                $groups[$document]['name'] = $name;
            }
        }

        if ($groups === []) {
            throw new InvalidArgumentException('No se encontraron cédulas en la planilla. Revise que sea el Excel PILA (filas de cotizantes debajo del encabezado).');
        }

        return [
            'header_row' => $headerRow,
            'max_col' => $highestCol,
            'id_col' => $idCol,
            'name_col' => $nameCol,
            'total_col' => $totalCol,
            'valor_cell' => $valorCell,
            'pension_period' => $pensionPeriod,
            'salud_period' => $saludPeriod,
            'groups' => $groups,
        ];
    }

    public static function normalizeCedula(string $raw): string
    {
        return preg_replace('/\D+/', '', $raw) ?? '';
    }

    /** @return array{0: int, 1: int}|null */
    private function findLabel(Worksheet $sheet, string $needle, int $maxRow, int $maxCol): ?array
    {
        $needle = $this->norm($needle);
        $prefix = null;
        for ($row = 1; $row <= $maxRow; $row++) {
            $hit = $this->findLabelInRow($sheet, $needle, $row, $maxCol, false);
            if ($hit === null) {
                continue;
            }
            $text = $this->norm((string) $sheet->getCell(Coordinate::stringFromColumnIndex($hit[1]).$hit[0])->getFormattedValue());
            if ($text === $needle) {
                return $hit;
            }
            $prefix ??= $hit;
        }

        return $prefix;
    }

    /** @return array{0: int, 1: int}|null */
    private function findLabelInRow(Worksheet $sheet, string $needle, int $row, int $maxCol, bool $normalizeNeedle = true): ?array
    {
        $needle = $normalizeNeedle ? $this->norm($needle) : $needle;
        $prefix = null;
        for ($col = 1; $col <= $maxCol; $col++) {
            $text = $this->norm((string) $sheet->getCell(Coordinate::stringFromColumnIndex($col).$row)->getFormattedValue());
            if ($text === $needle) {
                return [$row, $col];
            }
            if ($prefix === null && str_starts_with($text, $needle)) {
                $prefix = [$row, $col];
            }
        }

        return $prefix;
    }

    private function cellCedula(Cell $cell): string
    {
        $value = $cell->getValue();
        if (is_numeric($value)) {
            $asFloat = (float) $value;
            if (abs($asFloat - round($asFloat)) < 0.0001 && $asFloat > 0 && $asFloat < 1e15) {
                return self::normalizeCedula((string) (int) round($asFloat));
            }
        }

        return self::normalizeCedula((string) $cell->getFormattedValue());
    }

    private function cellAmount(Cell $cell): float
    {
        try {
            $value = $cell->getCalculatedValue();
        } catch (\Throwable) {
            $value = $cell->getValue();
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $raw = str_replace(['.', ' ', '$'], ['', '', ''], (string) $cell->getFormattedValue());
        $raw = str_replace(',', '.', $raw);

        return is_numeric($raw) ? (float) $raw : 0.0;
    }

    private function periodFromCell(Worksheet $sheet, string $address): ?string
    {
        $cell = $sheet->getCell($address);
        $value = $cell->getValue();
        if (is_numeric($value) && ExcelDate::isDateTime($cell)) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m');
        }

        $text = trim((string) $cell->getFormattedValue());
        if (preg_match('/(\d{4})[-\\/](\d{1,2})/', $text, $match) === 1) {
            return $match[1].'-'.str_pad($match[2], 2, '0', STR_PAD_LEFT);
        }

        return null;
    }

    private function norm(string $text): string
    {
        $text = strtr($text, [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ñ' => 'N',
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n',
        ]);

        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $text) ?? $text));
    }
}
