<?php

declare(strict_types=1);

namespace App\Services\Company;

use App\Models\Client;
use App\Models\GuardLog;
use App\Models\Location;
use App\Models\SupervisorShift;
use App\Models\SupervisorShiftReview;
use Illuminate\Validation\ValidationException;

final class RecordSupervisorProReviewService
{
    public function execute(
        SupervisorShift $shift,
        Client $client,
        string $notes,
        ?float $lat = null,
        ?float $lng = null,
    ): SupervisorShiftReview {
        if (! $shift->isOpen()) {
            throw ValidationException::withMessages([
                'shift' => 'El turno está cerrado.',
            ]);
        }

        if ((int) $client->security_company_id !== (int) $shift->security_company_id) {
            throw ValidationException::withMessages([
                'client_id' => 'El cliente no pertenece a esta empresa.',
            ]);
        }

        if (! $client->has_supervision) {
            throw ValidationException::withMessages([
                'client_id' => 'Este sitio no tiene Supervisión asignada.',
            ]);
        }

        $guardLogId = null;
        if ($client->has_access) {
            $locationId = Location::withoutGlobalScopes()
                ->where('client_id', $client->id)
                ->value('id');

            if ($locationId !== null) {
                $log = GuardLog::withoutGlobalScopes()->create([
                    'client_id' => $client->id,
                    'user_id' => $shift->user_id,
                    'location_id' => $locationId,
                    'log_time' => now(),
                    'type' => 'revista',
                    'description' => $notes !== '' ? $notes : 'Revista Pro en puesto (sin firma en portería).',
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'supervisor_name' => $shift->user?->name,
                    'signed_at' => now(),
                ]);
                $guardLogId = $log->id;
            }
        }

        return SupervisorShiftReview::query()->create([
            'supervisor_shift_id' => $shift->id,
            'client_id' => $client->id,
            'guard_log_id' => $guardLogId,
            'notes' => $notes,
            'latitude' => $lat,
            'longitude' => $lng,
            'recorded_at' => now(),
        ]);
    }
}
