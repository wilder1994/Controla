<?php

declare(strict_types=1);

namespace App\Support\Auth;

use App\Models\User;
use Illuminate\Validation\Rule;

final class LoginUsernameRules
{
    public const PATTERN = '/^[a-z][a-z0-9]*\.[a-z][a-z0-9]*\.[0-9]{4}$/';

    /** @return list<mixed> */
    public static function forChange(User $user): array
    {
        return [
            'required',
            'string',
            'max:40',
            'regex:'.self::PATTERN,
            Rule::unique('users', 'username')->ignore($user->id),
            Rule::notIn([(string) $user->username]),
        ];
    }
}
