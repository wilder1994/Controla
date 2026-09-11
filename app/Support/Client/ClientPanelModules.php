<?php

declare(strict_types=1);

namespace App\Support\Client;

final class ClientPanelModules
{
    public const VEHICLES = 'vehicles';

    public const PETS = 'pets';

    public const AUTHORIZATIONS = 'authorizations';

    public const DOORS = 'doors';

    /** @return list<string> */
    public static function optional(): array
    {
        return [
            self::VEHICLES,
            self::PETS,
            self::AUTHORIZATIONS,
            self::DOORS,
        ];
    }

    /** @return array<string, string> */
    public static function optionalLabels(): array
    {
        return [
            self::VEHICLES => 'Vehículos',
            self::PETS => 'Mascotas',
            self::AUTHORIZATIONS => 'Autorizaciones',
            self::DOORS => 'Puertas',
        ];
    }

    /** @return array<string, string> */
    public static function optionalHints(): array
    {
        return [
            self::VEHICLES => 'Censo de vehículos por nodo.',
            self::PETS => 'Censo de mascotas por nodo.',
            self::AUTHORIZATIONS => 'Autorizaciones del censo.',
            self::DOORS => 'Portería. Solo si el cliente ya tiene puertas creadas.',
        ];
    }

    /** @return array<string, bool> */
    public static function defaultOptional(): array
    {
        return [
            self::VEHICLES => true,
            self::PETS => true,
            self::AUTHORIZATIONS => true,
            self::DOORS => true,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $stored
     * @return array<string, bool>
     */
    public static function normalized(?array $stored): array
    {
        $defaults = self::defaultOptional();
        if ($stored === null) {
            return $defaults;
        }

        foreach ($defaults as $key => $default) {
            $defaults[$key] = array_key_exists($key, $stored)
                ? filter_var($stored[$key], FILTER_VALIDATE_BOOLEAN)
                : $default;
        }

        return $defaults;
    }
}
