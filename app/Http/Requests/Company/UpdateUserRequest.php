<?php

declare(strict_types=1);

namespace App\Http\Requests\Company;

use App\Http\Requests\Concerns\ValidatesManagedUser;
use App\Models\User;
use App\Support\Auth\AssignableRoles;
use Illuminate\Foundation\Http\FormRequest;

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
        return array_merge(
            $this->baseUserRules(false),
            $this->roleRule(AssignableRoles::forCompany()),
            $this->clientIdsRule(),
            [
                'name' => ['nullable', 'string', 'max:120'],
                'job_title' => ['required', 'string', 'max:80'],
            ],
        );
    }
}
