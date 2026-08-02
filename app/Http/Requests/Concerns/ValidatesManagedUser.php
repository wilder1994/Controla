<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use App\Support\Auth\AssignableRoles;
use Illuminate\Validation\Rule;

trait ValidatesManagedUser
{
    /** @return array<string, mixed> */
    protected function baseUserRules(bool $passwordRequired): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'password' => $passwordRequired
                ? ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()]
                : ['nullable', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<string, mixed> */
    protected function roleRule(array $allowedRoles): array
    {
        return [
            'role' => ['required', 'string', Rule::in($allowedRoles)],
        ];
    }

    /** @return array<string, mixed> */
    protected function clientIdsRule(): array
    {
        return [
            'client_ids' => ['nullable', 'array'],
            'client_ids.*' => ['integer', 'exists:clients,id'],
        ];
    }

    protected function roleRequiresClients(?string $role): bool
    {
        return $role !== null && in_array($role, AssignableRoles::requiringClientAssignment(), true);
    }
}
