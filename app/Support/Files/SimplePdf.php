<?php

declare(strict_types=1);

namespace App\Support\Files;

use setasign\Fpdi\Fpdi;

final class SimplePdf
{
    public static function write(string $absolutePath, string $title, string $body): void
    {
        self::writePages($absolutePath, [$title.': '.$body]);
    }

    /** @param list<string> $lines */
    public static function writePages(string $absolutePath, array $lines): void
    {
        $dir = dirname($absolutePath);
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $pdf = new Fpdi('P', 'mm', 'Letter');
        $pdf->SetAutoPageBreak(false);
        foreach ($lines as $line) {
            $pdf->AddPage();
            $pdf->SetFont('Helvetica', '', 14);
            $pdf->SetXY(20, 30);
            $latin = mb_convert_encoding($line, 'ISO-8859-1', 'UTF-8');
            $pdf->MultiCell(170, 8, $latin !== false ? $latin : $line);
        }
        $pdf->Output('F', $absolutePath);
    }
}
