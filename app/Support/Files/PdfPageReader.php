<?php

namespace App\Support\Files;

use setasign\Fpdi\Fpdi;
use Throwable;

final class PdfPageReader
{
    public static function count(string $absolutePath): int
    {
        try {
            $pdf = new Fpdi;
            $count = $pdf->setSourceFile($absolutePath);

            return max(1, (int) $count);
        } catch (Throwable) {
            $raw = (string) file_get_contents($absolutePath);
            preg_match_all('/\/Type\s*\/Page(?!s)/', $raw, $matches);

            return max(1, count($matches[0]));
        }
    }
}
