<?php

declare(strict_types=1);

namespace App\Services\Personnel;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class PilaPlanillaCells
{
    /** @var array<string, true> */
    public const ID_TYPES = [
        'CC' => true,
        'CE' => true,
        'TI' => true,
        'PA' => true,
        'RC' => true,
        'PE' => true,
        'PT' => true,
        'SC' => true,
        'CD' => true,
        'CN' => true,
        'PPT' => true,
        'PEP' => true,
    ];

    public static function normalizeCedula(string $raw): string
    {
        return preg_replace('/\D+/', '', $raw) ?? '';
    }

    public static function cedula(Cell $cell): string
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

    public static function amount(Cell $cell): float
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

    public static function text(Cell $cell): string
    {
        return self::norm((string) $cell->getFormattedValue());
    }

    public static function isIdType(string $raw): bool
    {
        $code = strtoupper(trim($raw));

        return isset(self::ID_TYPES[$code]);
    }

    public static function isDocumentNumber(string $digits): bool
    {
        $length = strlen($digits);

        return $length >= 5 && $length <= 12;
    }

    public static function periodFromCell(Worksheet $sheet, string $address): ?string
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

    public static function norm(string $text): string
    {
        $text = strtr($text, [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ñ' => 'N',
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n',
        ]);

        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $text) ?? $text));
    }
}
