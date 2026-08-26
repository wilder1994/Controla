<?php

declare(strict_types=1);

namespace App\Support\Employee;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

final class EmployeeExcelSchema
{
    public const DATA_SHEET = 'Empleados';

    public const INSTRUCTIONS_SHEET = 'Instrucciones';

    public const FILL_RED = 'FF0000';

    public const FILL_GREY = 'D0D0D0';

    /** @return list<string> */
    public static function headers(): array
    {
        return [
            'Tipo Documento de Identidad',
            'Nro. Documento de Identidad',
            'Ap. Paterno',
            'Ap. Materno',
            'Nombres',
            'Sexo',
            'Edad',
            'Tipo Colaborador',
            'Razón Social Cliente Final',
            'Instalaciones',
            'Sector (Ciudad)',
            'Puesto',
            'Cargo',
            'Mismo CC origen?',
            'Fecha Nacimiento',
            'Lugar de Nacimiento(Departamento)',
            'Lugar de Nacimiento(Ciudad)',
            'Teléfono Emergencia',
            'Contacto Emergencia',
            'Nacionalidad',
            'Discapacidad',
            'Email Ficha',
            'Lugar de Expedición Documento de Identificación',
            'Lugar de Expedición Documento de Identificación (Ciudad)',
            'Fecha de Expedición Documento de Identificación',
            'G.Sanguíneo',
        ];
    }

    /** @return list<string> */
    public static function keys(): array
    {
        return [
            'document_type',
            'document_number',
            'last_name_paternal',
            'last_name_maternal',
            'first_names',
            'sex',
            'age',
            'collaborator_type',
            'client_legal_name',
            'installation',
            'sector',
            'post',
            'job_title',
            'same_cost_center',
            'birth_date',
            'birth_department',
            'birth_city',
            'emergency_phone',
            'emergency_contact',
            'nationality',
            'has_disability',
            'email',
            'document_issue_department',
            'document_issue_city',
            'document_issued_at',
            'blood_group',
        ];
    }

    /** @return list<string> */
    public static function assignmentKeys(): array
    {
        return ['client_legal_name', 'installation', 'sector', 'post'];
    }

    public static function headerFillRgb(int $columnIndex): string
    {
        return match ($columnIndex) {
            1, 2, 3, 4, 5, 6, 8, 13, 15, 20, 26 => self::FILL_RED,
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
                    'Falta la columna «'.$expected.'». Usa el formato descargado o el maestro WM (A–Z).'
                );
            }
            $map[self::keys()[$position]] = $normalized[$key];
        }

        return $map;
    }
}
