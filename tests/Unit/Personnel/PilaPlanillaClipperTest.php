<?php

declare(strict_types=1);

namespace Tests\Unit\Personnel;

use App\Services\Personnel\PilaPlanillaClipper;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Tests\TestCase;
use ZipArchive;

final class PilaPlanillaClipperTest extends TestCase
{
    public function test_keeps_original_package_and_only_one_cotizante(): void
    {
        $png = storage_path('framework/testing/pila-dot.png');
        if (! is_dir(dirname($png))) {
            mkdir(dirname($png), 0775, true);
        }
        file_put_contents($png, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));

        $book = new Spreadsheet;
        $cover = $book->getActiveSheet();
        $cover->setTitle('Portada');
        $cover->setCellValue('A1', 'Portada Aportes');

        $sheet = $book->createSheet();
        $sheet->setTitle('mafars191');
        $sheet->mergeCells('A1:D3');
        $sheet->setCellValue('A1', 'DATOS GENERALES DEL APORTANTE');
        $sheet->setCellValue('BJ14', 'Valor');
        $sheet->setCellValue('BJ15', 999);
        $sheet->setCellValue('D20', 'Identificación');
        $sheet->setCellValue('D21', 'CC');
        $sheet->setCellValue('E21', '1112956880');
        $sheet->setCellValue('I21', 'FERNEY');
        $sheet->setCellValue('D22', 'CC');
        $sheet->setCellValue('E22', '1144001122');
        $sheet->setCellValue('I22', 'PILOTO');

        $drawing = new Drawing;
        $drawing->setPath($png);
        $drawing->setCoordinates('A1');
        $drawing->setWorksheet($sheet);

        $source = storage_path('framework/testing/pila-clip-src.xlsx');
        (new XlsxWriter($book))->save($source);

        $dest = storage_path('framework/testing/pila-clip-dest.xlsx');
        (new PilaPlanillaClipper)->writeClip($source, 1, 20, [22], 'BJ15', 180400.0, $dest);

        $out = IOFactory::load($dest);
        $this->assertSame('Portada Aportes', $out->getSheet(0)->getCell('A1')->getValue());
        $clipped = $out->getSheet(1);
        $this->assertSame('DATOS GENERALES DEL APORTANTE', $clipped->getCell('A1')->getValue());
        $this->assertContains('A1:D3', $clipped->getMergeCells());
        $this->assertSame(180400.0, (float) $clipped->getCell('BJ15')->getCalculatedValue());
        $this->assertSame(1144001122.0, (float) $clipped->getCell('E21')->getCalculatedValue());
        $this->assertSame('PILOTO', (string) $clipped->getCell('I21')->getFormattedValue());
        $this->assertSame('', trim((string) $clipped->getCell('E22')->getFormattedValue()));

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($dest) === true);
        $media = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            if (str_starts_with(str_replace('\\', '/', (string) $zip->getNameIndex($i)), 'xl/media/')) {
                $media++;
            }
        }
        $zip->close();
        $this->assertGreaterThan(0, $media);
    }
}
