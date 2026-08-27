<?php

declare(strict_types=1);

namespace App\Enums;

enum SupervisorFieldModule: string
{
    case Inventory = 'inventory';
    case Documents = 'documents';
    case Folders = 'folders';
    case Weapons = 'weapons';
    case Recommendations = 'recommendations';
    case Alarms = 'alarms';
    case Supports = 'supports';

    public function label(): string
    {
        return match ($this) {
            self::Inventory => 'Inventario',
            self::Documents => 'Documentos de puesto',
            self::Folders => 'Carpetas',
            self::Weapons => 'Armamento',
            self::Recommendations => 'Recomendaciones',
            self::Alarms => 'Alarmas',
            self::Supports => 'Apoyos',
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::Inventory => 'Estado operativo del puesto: buen estado, novedad o ya gestionado.',
            self::Documents => 'Control de minuta, novedades u otros documentos del puesto. No es el módulo comercial ni la correspondencia de Accesos.',
            self::Folders => 'Carpeta del puesto: completa o con faltantes.',
            self::Weapons => 'Inspección del arma en el puesto (serial observado, no un catálogo de armas).',
            self::Recommendations => 'Hallazgo que vive entre turnos hasta cerrarse. No se pierde al cerrar el turno.',
            self::Alarms => 'Prueba de alarma en el sitio.',
            self::Supports => 'Apoyo operativo. El sitio es opcional (puede ser en vía).',
        };
    }

    public function requiresClient(): bool
    {
        return $this === self::Alarms;
    }

    public function hangsOffReview(): bool
    {
        return match ($this) {
            self::Inventory, self::Documents, self::Folders, self::Weapons, self::Recommendations => true,
            default => false,
        };
    }
}
