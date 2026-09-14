<?php

declare(strict_types=1);

namespace App\Support\Personnel;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

final class XlsxPreviewHtml
{
    public static function fromPath(string $absolute, int $maxRows = 80, int $maxCols = 40): string
    {
        if (! is_file($absolute)) {
            return '<p>No se encontró el archivo.</p>';
        }

        try {
            $book = IOFactory::load($absolute);
            $sheet = $book->getSheet(0);
            $rows = min((int) $sheet->getHighestDataRow(), $maxRows);
            $cols = min(Coordinate::columnIndexFromString($sheet->getHighestColumn()), $maxCols);
            $html = '<table class="xlsx-preview-table">';
            for ($r = 1; $r <= $rows; $r++) {
                $html .= '<tr>';
                for ($c = 1; $c <= $cols; $c++) {
                    $text = htmlspecialchars(
                        (string) $sheet->getCell(Coordinate::stringFromColumnIndex($c).$r)->getFormattedValue(),
                        ENT_QUOTES | ENT_SUBSTITUTE,
                        'UTF-8',
                    );
                    $html .= '<td>'.$text.'</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</table>';
            $book->disconnectWorksheets();

            return $html;
        } catch (Throwable $e) {
            return '<p>No se pudo armar la vista previa del Excel.</p>';
        }
    }

    public static function isSpreadsheet(?string $mime, ?string $path): bool
    {
        $mime = strtolower((string) $mime);
        $path = strtolower((string) $path);

        return str_contains($mime, 'spreadsheet')
            || str_contains($mime, 'excel')
            || str_ends_with($path, '.xlsx')
            || str_ends_with($path, '.xls');
    }
}
