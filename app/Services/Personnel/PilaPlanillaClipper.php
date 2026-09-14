<?php

declare(strict_types=1);

namespace App\Services\Personnel;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class PilaPlanillaClipper
{
    /**
     * @param  list<int>  $dataRows
     */
    public function write(
        Worksheet $source,
        int $headerRow,
        int $maxCol,
        array $dataRows,
        string $valorCell,
        float $valorTotal,
        string $absoluteDest,
    ): void {
        $book = new Spreadsheet;
        $dest = $book->getActiveSheet();
        $dest->setTitle(mb_substr($source->getTitle(), 0, 31));

        for ($col = 1; $col <= $maxCol; $col++) {
            $letter = Coordinate::stringFromColumnIndex($col);
            $width = $source->getColumnDimension($letter)->getWidth();
            if ($width > 0) {
                $dest->getColumnDimension($letter)->setWidth($width);
            }
        }

        for ($row = 1; $row <= $headerRow; $row++) {
            $this->copyRow($source, $dest, $row, $row, $maxCol);
        }

        $map = [];
        $destRow = $headerRow + 1;
        foreach ($dataRows as $srcRow) {
            $map[$srcRow] = $destRow;
            $this->copyRow($source, $dest, $srcRow, $destRow, $maxCol);
            $destRow++;
        }

        foreach ($source->getMergeCells() as $range) {
            [$start, $end] = Coordinate::rangeBoundaries($range);
            $startCol = (int) $start[0];
            $startRow = (int) $start[1];
            $endCol = (int) $end[0];
            $endRow = (int) $end[1];

            if ($endRow <= $headerRow && $startRow <= $headerRow) {
                $dest->mergeCells($range);

                continue;
            }

            if ($startRow === $endRow && isset($map[$startRow])) {
                $newRow = $map[$startRow];
                $dest->mergeCells(
                    Coordinate::stringFromColumnIndex($startCol).$newRow.':'
                    .Coordinate::stringFromColumnIndex($endCol).$newRow
                );
            }
        }

        $dest->setCellValue($valorCell, $valorTotal);

        $directory = dirname($absoluteDest);
        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $writer = new Xlsx($book);
        $writer->save($absoluteDest);
        $book->disconnectWorksheets();
    }

    private function copyRow(Worksheet $source, Worksheet $dest, int $srcRow, int $destRow, int $maxCol): void
    {
        $height = $source->getRowDimension($srcRow)->getRowHeight();
        if ($height > 0) {
            $dest->getRowDimension($destRow)->setRowHeight($height);
        }

        for ($col = 1; $col <= $maxCol; $col++) {
            $from = Coordinate::stringFromColumnIndex($col).$srcRow;
            $to = Coordinate::stringFromColumnIndex($col).$destRow;
            $cell = $source->getCell($from);
            $dest->getCell($to)->setValue($cell->getValue());
            $dest->duplicateStyle($source->getStyle($from), $to);
        }
    }
}
