<?php

declare(strict_types=1);

namespace Tests\Unit\Personnel;

use App\Services\Personnel\RenderSpreadsheetPdfPreviewService;
use Tests\TestCase;

final class RenderSpreadsheetPdfPreviewServiceTest extends TestCase
{
    public function test_returns_cached_pdf_without_calling_libreoffice(): void
    {
        $dir = storage_path('app/testing/xlsx-preview');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $xlsx = $dir.DIRECTORY_SEPARATOR.'clip.xlsx';
        $pdf = $xlsx.'.preview.pdf';
        file_put_contents($xlsx, 'xlsx');
        file_put_contents($pdf, '%PDF-1.4 cached');
        touch($xlsx, time() - 10);
        touch($pdf, time());

        config(['services.libreoffice.binary' => 'off']);
        $service = new RenderSpreadsheetPdfPreviewService;

        $this->assertSame($pdf, $service->pdfAbsolute($xlsx));
        $this->assertSame(
            'testing/xlsx-preview/clip.xlsx.preview.pdf',
            str_replace('\\', '/', (string) $service->relativeDiskPath($pdf)),
        );

        $service->forget($xlsx);
        $this->assertFalse(is_file($pdf));
        @unlink($xlsx);
    }

    public function test_missing_binary_without_cache_returns_null(): void
    {
        $xlsx = storage_path('app/testing/xlsx-preview/missing-lo.xlsx');
        if (! is_dir(dirname($xlsx))) {
            mkdir(dirname($xlsx), 0775, true);
        }
        file_put_contents($xlsx, 'xlsx');
        config(['services.libreoffice.binary' => 'off']);

        $this->assertNull((new RenderSpreadsheetPdfPreviewService)->pdfAbsolute($xlsx));
        @unlink($xlsx);
    }
}
