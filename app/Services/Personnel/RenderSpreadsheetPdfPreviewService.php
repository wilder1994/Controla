<?php

declare(strict_types=1);

namespace App\Services\Personnel;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Throwable;

final class RenderSpreadsheetPdfPreviewService
{
    public function pdfAbsolute(string $xlsxAbsolute): ?string
    {
        if (! is_file($xlsxAbsolute)) {
            return null;
        }

        $pdf = $xlsxAbsolute.'.preview.pdf';
        if ($this->isFresh($xlsxAbsolute, $pdf)) {
            return $pdf;
        }

        $bin = $this->binary();
        if ($bin === null) {
            return is_file($pdf) ? $pdf : null;
        }

        $lockPath = $pdf.'.lock';
        $lock = fopen($lockPath, 'c');
        if ($lock === false) {
            return is_file($pdf) ? $pdf : null;
        }

        try {
            flock($lock, LOCK_EX);
            if ($this->isFresh($xlsxAbsolute, $pdf)) {
                return $pdf;
            }
            if ($this->convert($bin, $xlsxAbsolute, $pdf)) {
                return $pdf;
            }
        } catch (Throwable) {
            // Laragon sin LibreOffice, o fallo de soffice: no 500.
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
            if (is_file($lockPath)) {
                @unlink($lockPath);
            }
        }

        return is_file($pdf) ? $pdf : null;
    }

    public function forget(string $xlsxAbsolute): void
    {
        $pdf = $xlsxAbsolute.'.preview.pdf';
        if (is_file($pdf)) {
            File::delete($pdf);
        }
        $lock = $pdf.'.lock';
        if (is_file($lock)) {
            File::delete($lock);
        }
    }

    public function relativeDiskPath(string $absolute): ?string
    {
        $root = str_replace('\\', '/', storage_path('app')).'/';
        $path = str_replace('\\', '/', $absolute);
        if (! str_starts_with($path, $root)) {
            return null;
        }

        return ltrim(substr($path, strlen($root)), '/');
    }

    private function isFresh(string $xlsx, string $pdf): bool
    {
        return is_file($pdf)
            && filesize($pdf) > 8
            && filemtime($pdf) >= filemtime($xlsx);
    }

    private function binary(): ?string
    {
        $configured = strtolower(trim((string) config('services.libreoffice.binary', '')));
        if (in_array($configured, ['off', 'false', '0', 'disabled'], true)) {
            return null;
        }
        $configured = trim((string) config('services.libreoffice.binary', ''));
        if ($configured !== '') {
            return is_file($configured) || $this->isExecutable($configured) ? $configured : null;
        }

        foreach (['/usr/bin/soffice', '/usr/bin/libreoffice', '/usr/lib/libreoffice/program/soffice'] as $path) {
            if (is_file($path) && is_executable($path)) {
                return $path;
            }
        }

        return null;
    }

    private function isExecutable(string $path): bool
    {
        if (is_file($path) && is_executable($path)) {
            return true;
        }

        $base = basename($path);

        return $base !== '' && $base === $path;
    }

    private function convert(string $bin, string $xlsx, string $pdf): bool
    {
        $tmp = storage_path('app/libreoffice-tmp/'.bin2hex(random_bytes(8)));
        $home = storage_path('app/libreoffice-home');
        $profile = storage_path('app/libreoffice-profile');
        File::ensureDirectoryExists($tmp);
        File::ensureDirectoryExists($home);
        File::ensureDirectoryExists($profile);

        try {
            $result = Process::timeout(90)
                ->path($tmp)
                ->env([
                    'HOME' => $home,
                    'SAL_USE_VCLPLUGIN' => 'svp',
                ])
                ->run([
                    $bin,
                    '--headless',
                    '--nologo',
                    '--nofirststartwizard',
                    '--norestore',
                    '--nolockcheck',
                    '-env:UserInstallation='.$this->fileUri($profile),
                    '--convert-to',
                    'pdf',
                    '--outdir',
                    $tmp,
                    $xlsx,
                ]);

            if (! $result->successful()) {
                return false;
            }

            $produced = $this->firstPdf($tmp);
            if ($produced === null) {
                return false;
            }

            $bytes = file_get_contents($produced);
            if (! is_string($bytes) || ! str_starts_with($bytes, '%PDF')) {
                return false;
            }

            File::ensureDirectoryExists(dirname($pdf));

            return @rename($produced, $pdf) || File::copy($produced, $pdf);
        } finally {
            File::deleteDirectory($tmp);
        }
    }

    private function firstPdf(string $directory): ?string
    {
        $files = glob($directory.DIRECTORY_SEPARATOR.'*.pdf') ?: [];

        return isset($files[0]) && is_file($files[0]) ? $files[0] : null;
    }

    private function fileUri(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        if (! str_starts_with($path, '/')) {
            $path = '/'.$path;
        }

        return 'file://'.$path;
    }
}
