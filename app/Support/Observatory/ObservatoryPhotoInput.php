<?php

declare(strict_types=1);

namespace App\Support\Observatory;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

final class ObservatoryPhotoInput
{
    /** @return list<UploadedFile> */
    public static function files(Request $request): array
    {
        $photos = $request->file('photos', []);
        if ($photos instanceof UploadedFile) {
            $photos = [$photos];
        }
        if (! is_array($photos)) {
            $photos = [];
        }

        $out = [];
        foreach ($photos as $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                $out[] = $file;
            }
        }

        return array_slice($out, 0, 3);
    }

    /** @return array<string, mixed> */
    public static function optionalRules(): array
    {
        return [
            'photos' => ['nullable', 'array', 'max:3'],
            'photos.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
        ];
    }

    /** @return array<string, string> */
    public static function attributes(): array
    {
        return [
            'photos' => 'fotos',
            'photos.*' => 'foto',
            'photo' => 'foto',
        ];
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            'photos.max' => 'Puede adjuntar hasta tres fotos.',
        ];
    }
}
