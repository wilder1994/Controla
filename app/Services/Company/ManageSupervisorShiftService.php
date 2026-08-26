<?php

declare(strict_types=1);

namespace App\Services\Company;

use App\Enums\SupervisorShiftStatus;
use App\Models\SupervisorShift;
use App\Models\SupervisorShiftLocation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

final class ManageSupervisorShiftService
{
    public function open(User $user, ?int $kmStart = null, ?string $photoPath = null): SupervisorShift
    {
        $this->assertSupervisor($user);

        $open = SupervisorShift::query()
            ->where('user_id', $user->id)
            ->where('status', SupervisorShiftStatus::Open)
            ->first();

        if ($open !== null) {
            throw ValidationException::withMessages([
                'shift' => 'Ya tiene un turno abierto. Ciérrelo antes de iniciar otro.',
            ]);
        }

        return SupervisorShift::query()->create([
            'security_company_id' => (int) $user->security_company_id,
            'user_id' => $user->id,
            'status' => SupervisorShiftStatus::Open,
            'km_start' => $kmStart,
            'km_start_photo_path' => $photoPath,
            'started_at' => CarbonImmutable::now(),
        ]);
    }

    public function ping(SupervisorShift $shift, float $lat, float $lng, ?float $accuracy = null, string $source = 'app'): SupervisorShiftLocation
    {
        if (! $shift->isOpen()) {
            throw ValidationException::withMessages([
                'shift' => 'El turno está cerrado.',
            ]);
        }

        return $shift->locations()->create([
            'recorded_at' => now(),
            'latitude' => $lat,
            'longitude' => $lng,
            'accuracy' => $accuracy,
            'source' => $source,
        ]);
    }

    public function close(SupervisorShift $shift, ?int $kmEnd = null, ?string $photoPath = null): SupervisorShift
    {
        if (! $shift->isOpen()) {
            throw ValidationException::withMessages([
                'shift' => 'El turno ya está cerrado.',
            ]);
        }

        $traveled = null;
        if ($kmEnd !== null && $shift->km_start !== null && $kmEnd >= $shift->km_start) {
            $traveled = $kmEnd - $shift->km_start;
        }

        $shift->update([
            'status' => SupervisorShiftStatus::Closed,
            'km_end' => $kmEnd,
            'km_traveled' => $traveled,
            'km_end_photo_path' => $photoPath,
            'ended_at' => CarbonImmutable::now(),
        ]);

        return $shift->refresh();
    }

    public function currentFor(User $user): ?SupervisorShift
    {
        return SupervisorShift::query()
            ->where('user_id', $user->id)
            ->where('status', SupervisorShiftStatus::Open)
            ->first();
    }

    private function assertSupervisor(User $user): void
    {
        if (! $user->hasRole('supervisor') || $user->security_company_id === null) {
            throw ValidationException::withMessages([
                'shift' => 'Solo un supervisor de la empresa puede abrir turno Pro.',
            ]);
        }

        $company = $user->securityCompany;
        if ($company === null || ! $company->hasSupervisionPackage()) {
            throw ValidationException::withMessages([
                'shift' => 'La empresa no tiene Supervisión Pro contratada.',
            ]);
        }
    }
}
