<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\StructureMember;
use App\Models\User;

final class StructureMemberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('client.members.manage');
    }

    public function view(User $user, StructureMember $member): bool
    {
        return $user->can('client.members.manage')
            && $user->canAccessClient((int) $member->client_id);
    }

    public function create(User $user): bool
    {
        return $user->can('client.members.manage');
    }

    public function update(User $user, StructureMember $member): bool
    {
        return $user->can('client.members.manage')
            && $user->canAccessClient((int) $member->client_id);
    }

    public function delete(User $user, StructureMember $member): bool
    {
        return $this->update($user, $member);
    }
}
