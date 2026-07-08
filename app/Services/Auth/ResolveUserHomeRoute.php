<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;

final class ResolveUserHomeRoute
{
    public function forUser(User $user): string
    {
        if ($user->hasRole('super-admin')) {
            return route('admin.dashboard');
        }

        if ($user->hasRole('company-admin') && $user->security_company_id) {
            return route('company.dashboard');
        }

        if ($user->hasRole('client-admin')) {
            return route('client.dashboard');
        }

        if ($user->hasAnyRole(['guardia', 'supervisor', 'admin-accesos'])) {
            return route('access.dashboard');
        }

        if ($user->hasAnyRole(['resident', 'anfitrion'])) {
            return route('access.pre_authorizations.index');
        }

        return route('profile.edit');
    }
}
