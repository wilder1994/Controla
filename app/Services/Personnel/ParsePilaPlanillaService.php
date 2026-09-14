<?php

declare(strict_types=1);

namespace App\Services\Personnel;

use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

final class ParsePilaPlanillaService
{
    public const MAX_DATA_ROWS = 8000;

    public function __construct(
        private readonly DetectPilaPlanillaLayoutService $layout,
    ) {}

    /**
     * @return array{
     *     sheet_index: int,
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

        return $this->parseSpreadsheet(IOFactory::load($absolutePath));
    }

    /**
     * @return array{
     *     sheet_index: int,
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
        $layout = $this->layout->detect($spreadsheet);
        $sheet = $spreadsheet->getSheet($layout->sheetIndex);
        $groups = [];
        $dataCount = 0;

        for ($row = $layout->firstDataRow; $row <= $layout->lastDataRow; $row++) {
            $document = PilaPlanillaCells::cedula(
                $sheet->getCell(Coordinate::stringFromColumnIndex($layout->idCol).$row),
            );
            if (! PilaPlanillaCells::isDocumentNumber($document)) {
                continue;
            }

            $dataCount++;
            if ($dataCount > self::MAX_DATA_ROWS) {
                throw new InvalidArgumentException('La planilla supera el máximo de '.self::MAX_DATA_ROWS.' filas.');
            }

            $name = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($layout->nameCol).$row)->getFormattedValue());
            $total = PilaPlanillaCells::amount(
                $sheet->getCell(Coordinate::stringFromColumnIndex($layout->totalCol).$row),
            );

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
            'sheet_index' => $layout->sheetIndex,
            'header_row' => $layout->headerRow,
            'max_col' => $layout->maxCol,
            'id_col' => $layout->idCol,
            'name_col' => $layout->nameCol,
            'total_col' => $layout->totalCol,
            'valor_cell' => $layout->valorCell,
            'pension_period' => $layout->pensionPeriod,
            'salud_period' => $layout->saludPeriod,
            'groups' => $groups,
        ];
    }

    public static function normalizeCedula(string $raw): string
    {
        return PilaPlanillaCells::normalizeCedula($raw);
    }
}
