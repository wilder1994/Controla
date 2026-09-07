<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\StructureType;
use App\Models\User;

final class StructureTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('company.settings.manage')
            || $user->hasRole('super-admin');
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, StructureType $type): bool
    {
        return $this->owns($user, $type);
    }

    public function delete(User $user, StructureType $type): bool
    {
        return $this->owns($user, $type);
    }

    private function owns(User $user, StructureType $type): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->can('company.settings.manage')
            && (int) $user->security_company_id === (int) $type->security_company_id;
    }
};
