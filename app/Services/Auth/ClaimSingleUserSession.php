<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

final class ClaimSingleUserSession
{
    public const MESSAGE = 'Tienes una sesión abierta. ¿Cerrar la otra y seguir aquí?';

    public const CODE = 'session_active';

    public function isOccupied(User $user): bool
    {
        return filled($user->single_session_token) || $user->tokens()->exists();
    }

    public function assertCanClaim(User $user, bool $replace): void
    {
        if (! $this->isOccupied($user) || $replace) {
            return;
        }

        throw ValidationException::withMessages([
            'session_takeover' => self::MESSAGE,
        ]);
    }

    public function claim(User $user): string
    {
        $token = Str::random(40);
        $user->forceFill(['single_session_token' => $token])->save();
        $user->tokens()->delete();

        return $token;
    }

    public function release(User $user): void
    {
        $user->forceFill(['single_session_token' => null])->save();
        $user->tokens()->delete();
    }
}
