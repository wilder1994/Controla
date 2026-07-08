<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Structure;
use App\Models\User;

final class StructurePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('client.structures.manage');
    }

    public function view(User $user, Structure $structure): bool
    {
        return $user->can('client.structures.manage')
            && $user->canAccessClient((int) $structure->client_id);
    }

    public function create(User $user): bool
    {
        return $user->can('client.structures.manage');
    }

    public function update(User $user, Structure $structure): bool
    {
        return $user->can('client.structures.manage')
            && $user->canAccessClient((int) $structure->client_id);
    }

    public function delete(User $user, Structure $structure): bool
    {
        return $this->update($user, $structure);
    }
}
