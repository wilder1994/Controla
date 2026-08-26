<?php

declare(strict_types=1);

namespace App\Support\Supervision;

final class ShiftIntakeCatalog
{
    /**
     * Preoperacional de persona (EPP + equipo). Todos obligatorios para abrir turno.
     *
     * @return array<string, string>
     */
    public function ppe(): array
    {
        return [
            'helmet_ok' => 'Casco',
            'vest_ok' => 'Chaleco reflectivo',
            'boots_ok' => 'Botas',
            'gloves_ok' => 'Guantes',
            'raincoat_ok' => 'Impermeable',
            'radio_ok' => 'Radio o medio de comunicación operativo',
            'phone_ok' => 'Celular corporativo operativo',
            'flashlight_ok' => 'Linterna',
        ];
    }

    /**
     * Preoperacional del vehículo de flota (no es el vehículo de Accesos/portería).
     *
     * @return array<string, string>
     */
    public function vehicle(): array
    {
        return [
            'lights_ok' => 'Luces',
            'brakes_ok' => 'Frenos',
            'tires_ok' => 'Llantas',
            'oil_ok' => 'Nivel de aceite',
            'fuel_ok' => 'Combustible',
            'mirrors_ok' => 'Espejos',
        ];
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public function asList(array $items): array
    {
        $rows = [];
        foreach ($items as $key => $label) {
            $rows[] = ['key' => $key, 'label' => $label];
        }

        return $rows;
    }
}
