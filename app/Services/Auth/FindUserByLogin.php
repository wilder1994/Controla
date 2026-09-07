<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;

final class FindUserByLogin
{
    public function execute(string $login): ?User
    {
        $login = trim($login);
        if ($login === '') {
            return null;
        }

        return User::query()
            ->where(function ($query) use ($login): void {
                $query->where('username', $login)->orWhere('email', $login);
            })
            ->first();
    }
}
