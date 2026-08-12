<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use App\Http\Requests\Concerns\ValidatesManagedUser;
use App\Models\User;
use App\Support\Auth\AssignableRoles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateUserRequest extends FormRequest
{
    use ValidatesManagedUser;

    public function authorize(): bool
    {
        /** @var User $user */
        $user = $this->route('user');

        return $this->user()?->can('update', $user) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');

        return array_merge(
            $this->baseUserRules(false),
            $this->roleRule(AssignableRoles::forPlatform()),
            $this->clientIdsRule(),
            [
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('users', 'email')->ignore($user->id),
                ],
                'security_company_id' => ['nullable', 'integer', 'exists:security_companies,id'],
            ],
        );
    }
}
