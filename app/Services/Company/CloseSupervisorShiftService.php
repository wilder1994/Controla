<?php

declare(strict_types=1);

namespace App\Services\Company;

use App\Enums\SupervisorShiftStatus;
use App\Models\SupervisorShift;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CloseSupervisorShiftService
{
    public function execute(
        SupervisorShift $shift,
        int $kmEnd,
        UploadedFile $odometerPhoto,
        UploadedFile $selfiePhoto,
    ): SupervisorShift {
        if (! $shift->isOpen()) {
            throw ValidationException::withMessages([
                'shift' => 'El turno ya está cerrado.',
            ]);
        }

        if ($shift->km_start !== null && $kmEnd < (int) $shift->km_start) {
            throw ValidationException::withMessages([
                'km_end' => 'El kilometraje de cierre debe ser mayor o igual al de inicio ('.$shift->km_start.' km).',
            ]);
        }

        return DB::transaction(function () use ($shift, $kmEnd, $odometerPhoto, $selfiePhoto) {
            $shift->load('fleetVehicle');
            $dir = 'supervision/'.$shift->security_company_id.'/'.$shift->id;
            $odoPath = $odometerPhoto->storeAs($dir, 'end_odometer.jpg', 'local');
            $selfiePath = $selfiePhoto->storeAs($dir, 'end_selfie.jpg', 'local');

            $traveled = $shift->km_start !== null ? $kmEnd - (int) $shift->km_start : null;

            $shift->update([
                'status' => SupervisorShiftStatus::Closed,
                'km_end' => $kmEnd,
                'km_traveled' => $traveled,
                'km_end_photo_path' => $odoPath,
                'km_end_selfie_path' => $selfiePath,
                'ended_at' => now(),
            ]);

            if ($shift->fleetVehicle !== null) {
                $shift->fleetVehicle->update(['last_km' => $kmEnd]);
            }

            return $shift->fresh(['fleetVehicle']);
        });
    }
}
