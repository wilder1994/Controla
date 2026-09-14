<?php

namespace App\Enums;

enum DocumentFolder: string
{
    case HojaVida = 'hv';
    case Contratacion = 'contratacion';
    case Certificados = 'certificados';
    case Cursos = 'cursos';
    case Afiliaciones = 'afiliaciones';
    case Parafiscales = 'parafiscales';
    case Otros = 'otros';

    public function label(): string
    {
        return match ($this) {
            self::HojaVida => 'Historia Laboral',
            self::Contratacion => 'Contratación',
            self::Certificados => 'Certificados',
            self::Cursos => 'Cursos y capacitación',
            self::Afiliaciones => 'Afiliaciones',
            self::Parafiscales => 'Parafiscales',
            self::Otros => 'Otros',
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::HojaVida => 'Checklist indexado de ingreso y selección.',
            self::Contratacion => 'Checklist indexado de vinculación: contrato, ética, inducción, carné y carta de presentación.',
            self::Certificados => 'Checklist indexado: examen médico de ingreso, psicofísico y psicosensométrico.',
            self::Cursos => 'Catálogo Superintendencia + otro. Cada acta lleva fecha y entidad que dicta el curso.',
            self::Afiliaciones => 'Afiliaciones que hace la empresa al contratar.',
            self::Parafiscales => 'Recorte mensual de la planilla PILA (xlsx). Se carga desde el listado, no por PDF.',
            self::Otros => 'Soportes que no caben arriba. Tipo libre; máximo 20 por trabajador.',
        };
    }

    public function isIndexed(): bool
    {
        return $this !== self::Parafiscales;
    }

    public function naRoute(): ?string
    {
        return in_array($this, [self::Otros, self::Parafiscales], true)
            ? null
            : 'company.personnel-documents.na';
    }
}
