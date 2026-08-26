<?php

declare(strict_types=1);

namespace App\Support\Client;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

final class ClientExcelSchema
{
    public const DATA_SHEET = 'Clientes';

    public const INSTRUCTIONS_SHEET = 'Instrucciones';

    public const FILL_RED = 'FF0000';

    public const FILL_GREY = 'D0D0D0';

    /** @return list<string> */
    public static function headers(): array
    {
        return [
            'Tipo de cliente',
            'Nombre comercial',
            'Razón social',
            'Tipo documento',
            'Número documento',
            'Email',
            'Teléfono',
            'Representante',
            'Email representante',
            'Tipo de estructura',
            'Dirección',
            'Ciudad',
            'Departamento',
            'Accesos',
            'Supervisión Pro',
        ];
    }

    /** @return list<string> */
    public static function keys(): array
    {
        return [
            'party_type',
            'name',
            'legal_name',
            'document_type',
            'tax_id',
            'email',
            'phone',
            'representative_name',
            'representative_email',
            'structure_type',
            'address',
            'city',
            'department',
            'has_access',
            'has_supervision',
        ];
    }

    public static function headerFillRgb(int $columnIndex): string
    {
        return match ($columnIndex) {
            1, 2, 4, 5, 6, 10 => self::FILL_RED,
            default => self::FILL_GREY,
        };
    }

    public static function cellAddress(int $columnIndex, int $row): string
    {
        return Coordinate::stringFromColumnIndex($columnIndex).$row;
    }

    public static function normalize(string $header): string
    {
        $header = preg_replace('/^\x{FEFF}/u', '', $header) ?? $header;
        $header = trim(preg_replace('/\s+/u', ' ', $header) ?? $header);

        return mb_strtolower($header);
    }

    /**
     * @param  list<string>  $headers
     * @return array<string, int> key => 0-based index
     */
    public static function mapHeaderIndexes(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $index => $header) {
            $normalized[self::normalize((string) $header)] = $index;
        }

        $map = [];
        foreach (self::headers() as $position => $expected) {
            $key = self::normalize($expected);
            if (! array_key_exists($key, $normalized)) {
                throw new \InvalidArgumentException(
                    'Falta la columna «'.$expected.'». Usa el formato descargado.'
                );
            }
            $map[self::keys()[$position]] = $normalized[$key];
        }

        return $map;
    }
}
