<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use App\Http\Requests\Concerns\ValidatesManagedUser;
use App\Models\User;
use App\Support\Auth\AssignableRoles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreUserRequest extends FormRequest
{
    use ValidatesManagedUser;

    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return array_merge(
            $this->baseUserRules(true),
            $this->roleRule(AssignableRoles::forClient()),
            [
                'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            ],
        );
    }
}
