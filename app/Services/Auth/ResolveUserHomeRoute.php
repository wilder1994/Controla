<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use App\Support\Auth\GrantableModules;

final class ResolveUserHomeRoute
{
    public function forUser(User $user): string
    {
        if ($user->hasRole('super-admin')) {
            return route('admin.dashboard');
        }

        if ($user->hasAnyRole(['company-admin', 'colaborador']) && $user->security_company_id) {
            return GrantableModules::firstCompanyHome($user);
        }

        if ($user->hasAnyRole(['client-admin', 'client-installation-admin'])) {
            return route('client.dashboard');
        }

        if ($user->hasRole('supervisor')) {
            return route('supervisor.app-only');
        }

        if ($user->hasAnyRole(['guardia', 'admin-accesos'])) {
            return route('access.dashboard');
        }

        if ($user->hasAnyRole(['resident', 'anfitrion'])) {
            return route('resident.dashboard');
        }

        return route('profile.edit');
    }
}
