<?php

declare(strict_types=1);

namespace App\Support\Geo;

use App\Enums\ColombianAreaKind;

final class ColombianArea
{
    /** @var list<string> */
    private const COMMUNE_CITIES = [
        'armenia',
        'barranquilla',
        'bucaramanga',
        'cali',
        'cartagena',
        'cartagena de indias',
        'cucuta',
        'cúcuta',
        'ibague',
        'ibagué',
        'manizales',
        'medellin',
        'medellín',
        'monteria',
        'montería',
        'neiva',
        'pasto',
        'pereira',
        'popayan',
        'popayán',
        'santa marta',
        'santiago de cali',
        'sincelejo',
        'valledupar',
        'villavicencio',
    ];

    public static function classify(?string $name, ?string $city): ColombianAreaKind
    {
        $name = self::normalize($name);
        $city = self::normalize($city);

        if ($name === '') {
            return ColombianAreaKind::None;
        }

        if (str_contains($name, 'vereda')) {
            return ColombianAreaKind::Vereda;
        }
        if (str_contains($name, 'corregimiento')) {
            return ColombianAreaKind::Corregimiento;
        }
        if (str_contains($name, 'localidad')) {
            return ColombianAreaKind::Localidad;
        }
        if (str_contains($name, 'comuna')) {
            return ColombianAreaKind::Comuna;
        }

        if (self::isBogota($city)) {
            return ColombianAreaKind::Localidad;
        }
        if (in_array($city, self::COMMUNE_CITIES, true)) {
            return ColombianAreaKind::Comuna;
        }

        return ColombianAreaKind::None;
    }

    public static function persistableValue(?string $name, ?string $city): ?string
    {
        $text = trim((string) $name);
        if ($text === '') {
            return null;
        }

        return self::classify($text, $city)->applies() ? $text : null;
    }

    private static function isBogota(string $city): bool
    {
        return str_starts_with($city, 'bogota') || $city === 'distrito capital';
    }

    private static function normalize(?string $value): string
    {
        $text = mb_strtolower(trim((string) $value));
        $text = strtr($text, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u']);

        return $text;
    }
}
