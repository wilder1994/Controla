<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ClientLifecycle;
use App\Models\Client;
use App\Models\User;

final class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('company.clients.view')
            || $user->hasRole('super-admin');
    }

    public function view(User $user, Client $client): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        if (! $user->can('company.clients.view')) {
            return false;
        }

        if ((int) $user->security_company_id === (int) $client->security_company_id) {
            return true;
        }

        return $user->canAccessClient((int) $client->id);
    }

    public function create(User $user): bool
    {
        return $user->can('company.clients.manage')
            || $user->hasRole('super-admin');
    }

    public function update(User $user, Client $client): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->can('company.clients.manage')
            && (int) $user->security_company_id === (int) $client->security_company_id;
    }

    public function operate(User $user, Client $client): bool
    {
        return $user->canAccessClient((int) $client->id)
            && $client->is_active
            && $client->lifecycle === ClientLifecycle::Active;
    }
}
