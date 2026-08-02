<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Services\Auth\UserScopeResolver;
use App\Support\Auth\AssignableRoles;
use App\Support\Auth\UserManagementContext;

final class UserPolicy
{
    public function __construct(
        private readonly UserScopeResolver $scopeResolver,
    ) {}

    public function viewAny(User $actor): bool
    {
        return $actor->can('platform.users.view')
            || $actor->can('company.users.assign')
            || $actor->can('client.users.manage');
    }

    public function view(User $actor, User $target): bool
    {
        if (! $this->viewAny($actor)) {
            return false;
        }

        return $this->scopeResolver->canManage($actor, $target);
    }

    public function create(User $actor): bool
    {
        return $actor->can('platform.users.manage')
            || $actor->can('company.users.assign')
            || $actor->can('client.users.manage');
    }

    public function update(User $actor, User $target): bool
    {
        if (! $this->view($actor, $target)) {
            return false;
        }

        if ($target->hasRole('super-admin') && ! $actor->hasRole('super-admin')) {
            return false;
        }

        return $actor->can('platform.users.manage')
            || $actor->can('company.users.assign')
            || $actor->can('client.users.manage');
    }

    public function assignRole(User $actor, User $target, string $role): bool
    {
        if (! $this->update($actor, $target) && $actor->id !== $target->id) {
            return false;
        }

        $context = $this->contextFor($actor);

        return match ($context) {
            UserManagementContext::Platform => in_array($role, AssignableRoles::forPlatform(), true),
            UserManagementContext::Company => in_array($role, AssignableRoles::forCompany(), true),
            UserManagementContext::Client => in_array($role, AssignableRoles::forClient(), true),
        };
    }

    private function contextFor(User $actor): UserManagementContext
    {
        if ($actor->can('platform.users.manage')) {
            return UserManagementContext::Platform;
        }

        if ($actor->can('company.users.assign')) {
            return UserManagementContext::Company;
        }

        return UserManagementContext::Client;
    }
}
