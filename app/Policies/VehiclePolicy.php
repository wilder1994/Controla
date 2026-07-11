<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Vehicle;

final class VehiclePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('client.vehicles.manage');
    }

    public function view(User $user, Vehicle $vehicle): bool
    {
        return $user->can('client.vehicles.manage')
            && $user->canAccessClient((int) $vehicle->client_id);
    }

    public function create(User $user): bool
    {
        return $user->can('client.vehicles.manage');
    }

    public function update(User $user, Vehicle $vehicle): bool
    {
        return $user->can('client.vehicles.manage')
            && $user->canAccessClient((int) $vehicle->client_id);
    }

    public function delete(User $user, Vehicle $vehicle): bool
    {
        return $this->update($user, $vehicle);
    }
}
