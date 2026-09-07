<?php

declare(strict_types=1);

namespace App\Enums;

use DateTimeInterface;

enum SupervisorFieldSheetKind: string
{
    case Review = 'review';
    case Alarm = 'alarm';
    case Support = 'support';
    case Document = 'document';

    public function label(): string
    {
        return match ($this) {
            self::Review => 'Revista',
            self::Alarm => 'Alarma',
            self::Support => 'Apoyo',
            self::Document => 'Documentos',
        };
    }

    public function code(): string
    {
        return match ($this) {
            self::Review => 'R',
            self::Alarm => 'A',
            self::Support => 'S',
            self::Document => 'D',
        };
    }

    public function folio(int $id, DateTimeInterface $recordedAt): string
    {
        return sprintf('FC-%s-%s%06d', $recordedAt->format('Y'), $this->code(), $id);
    }

    public static function fromStandaloneModule(SupervisorFieldModule $module): ?self
    {
        return match ($module) {
            SupervisorFieldModule::Alarms => self::Alarm,
            SupervisorFieldModule::Supports => self::Support,
            SupervisorFieldModule::Documents => self::Document,
            default => null,
        };
    }

    public function standaloneModule(): ?SupervisorFieldModule
    {
        return match ($this) {
            self::Alarm => SupervisorFieldModule::Alarms,
            self::Support => SupervisorFieldModule::Supports,
            self::Document => SupervisorFieldModule::Documents,
            self::Review => null,
        };
    }
}
