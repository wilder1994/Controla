<?php

declare(strict_types=1);

namespace App\Support\User;

use Illuminate\Http\UploadedFile;

final class UserAvatarUploader
{
    public static function store(?UploadedFile $file): ?string
    {
        if ($file === null) {
            return null;
        }

        return $file->store('avatars', 'public');
    }
}
