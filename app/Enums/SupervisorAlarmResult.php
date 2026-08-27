<?php

declare(strict_types=1);

namespace App\Enums;

enum SupervisorAlarmResult: string
{
    case Ok = 'ok';
    case Fail = 'fail';
    case Real = 'real';
    case FalseAlarm = 'false_alarm';
    case NotFound = 'not_found';

    public function label(): string
    {
        return match ($this) {
            self::Ok => 'OK',
            self::Fail => 'Falla',
            self::Real => 'Real',
            self::FalseAlarm => 'Falsa',
            self::NotFound => 'No ubicada',
        };
    }

    /**
     * @return list<self>
     */
    public static function forKind(SupervisorAlarmKind $kind): array
    {
        return match ($kind) {
            SupervisorAlarmKind::Test => [self::Ok, self::Fail],
            SupervisorAlarmKind::Response => [self::Real, self::FalseAlarm, self::NotFound],
        };
    }
}
