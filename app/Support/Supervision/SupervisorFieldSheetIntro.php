<?php

declare(strict_types=1);

namespace App\Support\Supervision;

final class SupervisorFieldSheetIntro
{
    public const DEFAULT = 'En ejercicio de las obligaciones de control y supervisión del servicio de vigilancia y seguridad privada, de conformidad con el Decreto 356 de 1994 (Estatuto de Vigilancia y Seguridad Privada) y la normativa que lo desarrolla, esta empresa deja constancia de la revista practicada al puesto de servicio del cliente indicado, con verificación de personal, medios, documentación y novedades halladas en sitio.';

    public static function resolve(?string $stored): string
    {
        $text = trim((string) $stored);

        return $text !== '' ? $text : self::DEFAULT;
    }
}
