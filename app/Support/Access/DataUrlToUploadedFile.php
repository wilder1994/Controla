<?php

declare(strict_types=1);

namespace App\Support\Access;

use Illuminate\Http\UploadedFile;

final class DataUrlToUploadedFile
{
    public static function make(?string $dataUrl, string $filename): ?UploadedFile
    {
        if ($dataUrl === null || ! str_starts_with($dataUrl, 'data:image/')) {
            return null;
        }

        if (! preg_match('#^data:image/(png|jpeg|jpg|webp);base64,(.+)$#', $dataUrl, $m)) {
            return null;
        }

        $binary = base64_decode($m[2], true);
        if ($binary === false || $binary === '') {
            return null;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'pta');
        if ($tmp === false) {
            return null;
        }
        file_put_contents($tmp, $binary);

        $ext = $m[1] === 'jpeg' ? 'jpg' : $m[1];

        return new UploadedFile($tmp, $filename.'.'.$ext, 'image/'.$m[1], null, true);
    }
}
