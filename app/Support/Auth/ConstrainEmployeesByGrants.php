<?php

declare(strict_types=1);

namespace App\Support\Auth;

use App\Enums\AccessGrantScope;
use App\Models\User;
use App\Models\UserModuleGrant;
use Illuminate\Database\Eloquent\Builder;

final class ConstrainEmployeesByGrants
{
    public function apply(Builder $query, User $user, string $module = 'employees'): void
    {
        if ($user->hasRole('super-admin')) {
            return;
        }

        $user->loadMissing('moduleGrants');

        $hasCompany = $user->moduleGrants->contains(
            fn (UserModuleGrant $row) => $row->scope === AccessGrantScope::Company
                && $row->module === $module
                && $row->level->value !== 'none',
        );

        if ($hasCompany) {
            return;
        }

        $clientIds = $user->moduleGrants
            ->filter(fn (UserModuleGrant $row) => $row->scope === AccessGrantScope::Client && $row->module === $module)
            ->map(fn (UserModuleGrant $row) => (int) $row->scope_id)
            ->values()
            ->all();
        $installationIds = $user->moduleGrants
            ->filter(fn (UserModuleGrant $row) => $row->scope === AccessGrantScope::Installation && $row->module === $module)
            ->map(fn (UserModuleGrant $row) => (int) $row->scope_id)
            ->values()
            ->all();

        if ($clientIds === [] && $installationIds === []) {
            $query->whereRaw('0 = 1');

            return;
        }

        $query->where(function (Builder $inner) use ($clientIds, $installationIds): void {
            if ($clientIds !== []) {
                $inner->orWhereHas(
                    'supervisorPosts',
                    fn (Builder $posts) => $posts->whereIn('client_id', $clientIds),
                );
            }
            if ($installationIds !== []) {
                $inner->orWhereHas(
                    'supervisorPosts',
                    fn (Builder $posts) => $posts->whereIn('installation_id', $installationIds),
                );
            }
        });
    }
}
