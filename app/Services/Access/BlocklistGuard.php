<?php

namespace App\Services\Access;

use App\Models\Blocklist;
use App\Models\Resident;
use App\Models\Vehicle;
use App\Models\Visitor;

class BlocklistGuard
{
    /**
     * @return Blocklist|null Entry activa de bloqueo para una persona.
     */
    public function checkPerson(?Visitor $visitor = null, ?Resident $resident = null, ?string $documentNumber = null): ?Blocklist
    {
        $documentNumber = $documentNumber
            ?? $visitor?->document_number
            ?? $resident?->document_number;

        if ($documentNumber === null) {
            return null;
        }

        return Blocklist::query()
            ->active()
            ->persons()
            ->with('blockable')
            ->get()
            ->first(function (Blocklist $entry) use ($visitor, $resident, $documentNumber): bool {
                $blocked = $entry->blockable;

                if ($blocked === null) {
                    return false;
                }

                if ($visitor !== null && $entry->normalizedType() === Blocklist::TYPE_VISITOR && (int) $entry->blockable_id === (int) $visitor->id) {
                    return true;
                }

                if ($resident !== null && $entry->normalizedType() === Blocklist::TYPE_RESIDENT && (int) $entry->blockable_id === (int) $resident->id) {
                    return true;
                }

                return isset($blocked->document_number) && (string) $blocked->document_number === (string) $documentNumber;
            });
    }

    /**
     * @return Blocklist|null Entry activa de bloqueo para un vehículo.
     */
    public function checkVehicle(?Vehicle $vehicle = null, ?string $plate = null): ?Blocklist
    {
        $plate = strtoupper((string) ($plate ?? $vehicle?->plate));

        if ($plate === '') {
            return null;
        }

        return Blocklist::query()
            ->active()
            ->vehicles()
            ->with('blockable')
            ->get()
            ->first(function (Blocklist $entry) use ($vehicle, $plate): bool {
                if ($vehicle !== null && (int) $entry->blockable_id === (int) $vehicle->id) {
                    return true;
                }

                $blocked = $entry->blockable;

                return $blocked !== null && strtoupper((string) $blocked->plate) === $plate;
            });
    }
}
