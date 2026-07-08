<?php

declare(strict_types=1);

namespace App\Enums;

enum MemberType: string
{
    case Owner = 'owner';
    case Tenant = 'tenant';
    case FamilyMember = 'family_member';
    case TemporaryGuest = 'temporary_guest';
    case Employee = 'employee';
    case Administrator = 'administrator';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Propietario',
            self::Tenant => 'Arrendatario',
            self::FamilyMember => 'Familiar',
            self::TemporaryGuest => 'Invitado permanente',
            self::Employee => 'Empleado',
            self::Administrator => 'Administrador',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type) => [$type->value => $type->label()])
            ->all();
    }
}
