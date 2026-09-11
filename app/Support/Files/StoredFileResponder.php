<?php

namespace App\Support\Files;

use Symfony\Component\HttpFoundation\StreamedResponse;

final class StoredFileResponder
{
    public static function absolute(string $diskPath): string
    {
        return storage_path('app/'.$diskPath);
    }

    public static function stream(string $diskPath, string $downloadName, string $mime, bool $inline): StreamedResponse
    {
        $absolute = self::absolute($diskPath);
        abort_unless(is_file($absolute), 404);

        $disposition = $inline ? 'inline' : 'attachment';

        return response()->stream(function () use ($absolute): void {
            $handle = fopen($absolute, 'rb');
            if ($handle === false) {
                return;
            }
            fpassthru($handle);
            fclose($handle);
        }, 200, [
            'Content-Type' => $mime !== '' ? $mime : 'application/octet-stream',
            'Content-Disposition' => $disposition.'; filename="'.str_replace('"', '', $downloadName).'"',
            'Content-Length' => (string) filesize($absolute),
        ]);
    }
}
