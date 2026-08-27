<?php

declare(strict_types=1);

namespace App\Enums;

enum SupervisorFieldModule: string
{
    case Inventory = 'inventory';
    case ControlBooks = 'control_books';
    case Folders = 'folders';
    case Weapons = 'weapons';
    case Recommendations = 'recommendations';
    case Alarms = 'alarms';
    case Supports = 'supports';
    case Documents = 'documents';

    public function label(): string
    {
        return match ($this) {
            self::Inventory => 'Inventario',
            self::ControlBooks => 'Libros de control',
            self::Folders => 'Carpetas',
            self::Weapons => 'Armamento',
            self::Recommendations => 'Recomendaciones',
            self::Alarms => 'Alarmas',
            self::Supports => 'Apoyos',
            self::Documents => 'Documentos',
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::Inventory => 'Pase revista a cada elemento del puesto: tipo, estado y observación.',
            self::ControlBooks => 'Libros del puesto: tipo, novedad y observación.',
            self::Folders => 'Carpeta del puesto: completa o con faltantes.',
            self::Weapons => 'Revista del arma: identificación, novedad y aseo opcional con foto.',
            self::Recommendations => 'Hasta tres riesgos del puesto: probabilidad, impacto, consecuencia y evidencia.',
            self::Alarms => 'Prueba o atención de alarma en el sitio: tipo, modalidad y resultado.',
            self::Supports => 'Apoyo operativo (tipo + motivo). El sitio es opcional (puede ser en vía).',
            self::Documents => 'Papeles que recogen o entregan en el turno. Sin cliente ni puesto.',
        };
    }

    public function requiresClient(): bool
    {
        return $this === self::Alarms;
    }

    public function hangsOffReview(): bool
    {
        return match ($this) {
            self::Inventory, self::ControlBooks, self::Folders, self::Weapons, self::Recommendations => true,
            default => false,
        };
    }
}
