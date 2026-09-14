<?php

declare(strict_types=1);

namespace App\Services\Personnel;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

final class PilaPlanillaClipper
{
    /**
     * @return array{path: string, merges: array<int, list<array{0: int, 1: int}>>}
     */
    public function buildHeaderTemplate(Worksheet $source, int $headerRow, int $maxCol, string $absolutePath): array
    {
        $book = new Spreadsheet;
        $dest = $book->getActiveSheet();
        $dest->setTitle(mb_substr($source->getTitle(), 0, 31));
        $this->copySetup($source, $dest);

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

        if ($source->getHighestRow() > $headerRow) {
            $this->copyRowStyle($source, $dest, $headerRow + 1, $headerRow + 1, $maxCol);
        }

        foreach ($source->getMergeCells() as $range) {
            [$start, $end] = Coordinate::rangeBoundaries($range);
            $startRow = (int) $start[1];
            $endRow = (int) $end[1];
            if ($startRow <= $headerRow && $endRow <= $headerRow) {
                try {
                    $dest->mergeCells($range);
                } catch (Throwable) {
                }
            }
        }

        $this->copyDrawings($source, $dest);

        $directory = dirname($absolutePath);
        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $writer = new Xlsx($book);
        $writer->save($absolutePath);
        $book->disconnectWorksheets();

        return [
            'path' => $absolutePath,
            'merges' => $this->dataRowMerges($source, $headerRow),
        ];
    }

    /**
     * @param  list<list<mixed>>  $rows
     * @param  array<int, list<array{0: int, 1: int}>>  $mergesBySrcRow
     * @param  list<int>  $srcRows
     */
    public function writeFromValues(
        string $templatePath,
        int $headerRow,
        array $rows,
        array $srcRows,
        array $mergesBySrcRow,
        string $valorCell,
        float $valorTotal,
        string $absoluteDest,
    ): void {
        $book = IOFactory::load($templatePath);
        $dest = $book->getActiveSheet();
        $destRow = $headerRow + 1;

        $styleRow = $headerRow + 1;
        foreach ($rows as $index => $values) {
            $srcRow = $srcRows[$index] ?? 0;
            if ($destRow !== $styleRow) {
                $this->copyRowStyle($dest, $dest, $styleRow, $destRow, max(1, count($values)));
            }
            foreach ($values as $offset => $value) {
                $dest->getCell(Coordinate::stringFromColumnIndex($offset + 1).$destRow)->setValue($value);
            }
            foreach ($mergesBySrcRow[$srcRow] ?? [] as $span) {
                try {
                    $dest->mergeCells(
                        Coordinate::stringFromColumnIndex($span[0]).$destRow.':'
                        .Coordinate::stringFromColumnIndex($span[1]).$destRow
                    );
                } catch (Throwable) {
                }
            }
            $destRow++;
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

    /**
     * @return list<mixed>
     */
    public function exportRow(Worksheet $source, int $row, int $maxCol): array
    {
        $values = [];
        for ($col = 1; $col <= $maxCol; $col++) {
            $values[] = $this->exportCell($source->getCell(Coordinate::stringFromColumnIndex($col).$row));
        }

        return $values;
    }

    /**
     * @return array<int, list<array{0: int, 1: int}>>
     */
    private function dataRowMerges(Worksheet $source, int $headerRow): array
    {
        $byRow = [];
        foreach ($source->getMergeCells() as $range) {
            [$start, $end] = Coordinate::rangeBoundaries($range);
            $startCol = (int) $start[0];
            $startRow = (int) $start[1];
            $endCol = (int) $end[0];
            $endRow = (int) $end[1];
            if ($startRow === $endRow && $startRow > $headerRow) {
                $byRow[$startRow][] = [$startCol, $endCol];
            }
        }

        return $byRow;
    }

    private function copyRow(Worksheet $source, Worksheet $dest, int $srcRow, int $destRow, int $maxCol): void
    {
        $this->copyRowStyle($source, $dest, $srcRow, $destRow, $maxCol);

        for ($col = 1; $col <= $maxCol; $col++) {
            $from = Coordinate::stringFromColumnIndex($col).$srcRow;
            $to = Coordinate::stringFromColumnIndex($col).$destRow;
            $dest->getCell($to)->setValue($this->exportCell($source->getCell($from)));
        }
    }

    private function copyRowStyle(Worksheet $source, Worksheet $dest, int $srcRow, int $destRow, int $maxCol): void
    {
        $height = $source->getRowDimension($srcRow)->getRowHeight();
        if ($height > 0) {
            $dest->getRowDimension($destRow)->setRowHeight($height);
        }

        for ($col = 1; $col <= $maxCol; $col++) {
            $from = Coordinate::stringFromColumnIndex($col).$srcRow;
            $to = Coordinate::stringFromColumnIndex($col).$destRow;
            try {
                $dest->duplicateStyle($source->getStyle($from), $to);
            } catch (Throwable) {
            }
        }
    }

    private function exportCell(\PhpOffice\PhpSpreadsheet\Cell\Cell $cell): mixed
    {
        $value = $cell->getValue();
        if (is_string($value) && str_starts_with($value, '=')) {
            try {
                return $cell->getCalculatedValue();
            } catch (Throwable) {
                return PilaPlanillaCells::amount($cell);
            }
        }

        return $value;
    }

    private function copySetup(Worksheet $source, Worksheet $dest): void
    {
        try {
            $dest->getPageSetup()->setOrientation($source->getPageSetup()->getOrientation());
            $dest->getPageSetup()->setPaperSize($source->getPageSetup()->getPaperSize());
            $dest->getPageSetup()->setFitToPage($source->getPageSetup()->getFitToPage());
            $dest->getPageSetup()->setFitToWidth($source->getPageSetup()->getFitToWidth());
            $dest->getPageSetup()->setFitToHeight($source->getPageSetup()->getFitToHeight());
            $dest->getPageMargins()->setTop($source->getPageMargins()->getTop());
            $dest->getPageMargins()->setRight($source->getPageMargins()->getRight());
            $dest->getPageMargins()->setLeft($source->getPageMargins()->getLeft());
            $dest->getPageMargins()->setBottom($source->getPageMargins()->getBottom());
            $footer = $source->getHeaderFooter();
            $dest->getHeaderFooter()->setOddHeader($footer->getOddHeader());
            $dest->getHeaderFooter()->setOddFooter($footer->getOddFooter());
            $dest->getHeaderFooter()->setEvenHeader($footer->getEvenHeader());
            $dest->getHeaderFooter()->setEvenFooter($footer->getEvenFooter());
            $dest->setShowGridlines($source->getShowGridlines());
            $freeze = $source->getFreezePane();
            if (is_string($freeze) && $freeze !== '') {
                $dest->freezePane($freeze);
            }
            $printArea = $source->getPageSetup()->getPrintArea();
            if (is_string($printArea) && $printArea !== '') {
                $dest->getPageSetup()->setPrintArea($printArea);
            }
            $srcBook = $source->getParent();
            $destBook = $dest->getParent();
            if ($srcBook instanceof Spreadsheet && $destBook instanceof Spreadsheet) {
                $destBook->getDefaultStyle()->applyFromArray($srcBook->getDefaultStyle()->exportArray());
            }
        } catch (Throwable) {
        }
    }

    private function copyDrawings(Worksheet $source, Worksheet $dest): void
    {
        foreach ($source->getDrawingCollection() as $drawing) {
            try {
                if ($drawing instanceof MemoryDrawing) {
                    $clone = new MemoryDrawing;
                    $clone->setName($drawing->getName());
                    $clone->setDescription($drawing->getDescription());
                    $clone->setCoordinates($drawing->getCoordinates());
                    $clone->setOffsetX($drawing->getOffsetX());
                    $clone->setOffsetY($drawing->getOffsetY());
                    $clone->setWidth($drawing->getWidth());
                    $clone->setHeight($drawing->getHeight());
                    $clone->setImageResource($drawing->getImageResource());
                    $clone->setRenderingFunction($drawing->getRenderingFunction());
                    $clone->setMimeType($drawing->getMimeType());
                    $clone->setWorksheet($dest);

                    continue;
                }

                if ($drawing instanceof Drawing && is_string($drawing->getPath()) && $drawing->getPath() !== '') {
                    $clone = new Drawing;
                    $clone->setName($drawing->getName());
                    $clone->setDescription($drawing->getDescription());
                    $clone->setPath($drawing->getPath(), false);
                    $clone->setCoordinates($drawing->getCoordinates());
                    $clone->setOffsetX($drawing->getOffsetX());
                    $clone->setOffsetY($drawing->getOffsetY());
                    $clone->setWidth($drawing->getWidth());
                    $clone->setHeight($drawing->getHeight());
                    $clone->setWorksheet($dest);
                }
            } catch (Throwable) {
            }
        }
    }
}
