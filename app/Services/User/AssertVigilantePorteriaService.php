<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Models\Employee;
use App\Models\Location;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class AssertVigilantePorteriaService
{
    public function assert(?int $employeeId, int $clientId, ?User $existingUser = null): void
    {
        $employee = $employeeId !== null && $employeeId > 0
            ? Employee::query()->find($employeeId)
            : $existingUser?->employee;

        if ($employee !== null) {
            $this->assertEmployeeCanOperate($employee, $clientId);

            return;
        }

        if (! $this->clientHasDoors($clientId)) {
            throw ValidationException::withMessages([
                'client_ids' => 'Este cliente no tiene puertas. Sin puertas no hay portería que operar.',
            ]);
        }
    }

    private function assertEmployeeCanOperate(Employee $employee, int $clientId): void
    {
        $posts = $employee->supervisorPosts()
            ->where('client_id', $clientId)
            ->where('is_active', true)
            ->with('installation')
            ->get();

        if ($posts->isEmpty()) {
            throw ValidationException::withMessages([
                'client_ids' => 'El vigilante debe estar asignado a un puesto de una instalación de este cliente.',
            ]);
        }

        $withDoors = $posts->first(function ($post): bool {
            $installation = $post->installation;

            return $installation !== null && $installation->is_active && $installation->hasDoors();
        });

        if ($withDoors === null) {
            throw ValidationException::withMessages([
                'client_ids' => 'La instalación del puesto no tiene puertas. Sin puertas no hay necesidad de operar portería.',
            ]);
        }
    }

    private function clientHasDoors(int $clientId): bool
    {
        return Location::query()
            ->withoutGlobalScopes()
            ->where('client_id', $clientId)
            ->where('is_active', true)
            ->whereNotNull('installation_id')
            ->exists();
    }
}
