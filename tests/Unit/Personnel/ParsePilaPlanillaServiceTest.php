<?php

declare(strict_types=1);

namespace Tests\Unit\Personnel;

use App\Services\Personnel\DetectPilaPlanillaLayoutService;
use App\Services\Personnel\ParsePilaPlanillaService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

final class ParsePilaPlanillaServiceTest extends TestCase
{
    public function test_reads_cotizantes_on_named_sheet_past_aportante_identificacion(): void
    {
        $book = new Spreadsheet;
        $cover = $book->getActiveSheet();
        $cover->setTitle('Portada');
        $cover->setCellValue('A1', 'Planilla');
        $cover->setCellValue('B8', 'Identificación del aportante');
        $cover->setCellValue('C8', '900123456');

        $sheet = $book->createSheet();
        $sheet->setTitle('mafars191');
        $sheet->setCellValue('B8', 'Identificación del aportante');
        $sheet->setCellValue('C8', 'NIT 900123456');
        $sheet->setCellValue('B14', 'Pensión');
        $sheet->setCellValue('H14', 'Salud');
        $sheet->setCellValue('BJ14', 'Valor');
        $sheet->setCellValue('B15', '2026-08');
        $sheet->setCellValue('H15', '2026-09');
        $sheet->setCellValue('BJ15', 0);
        $sheet->setCellValue('D20', 'Identificación');
        $sheet->setCellValue('I20', 'Nombre');
        $sheet->setCellValue('BW20', 'Total Aportes');
        $sheet->setCellValue('D61', 'CC');
        $sheet->setCellValue('E61', '1112956880');
        $sheet->setCellValue('I61', 'ARREDONDO FERNEY');
        $sheet->setCellValue('BW61', 180400);
        $sheet->setCellValue('D62', 'CC');
        $sheet->setCellValue('E62', '1112956880');
        $sheet->setCellValue('I62', 'ARREDONDO FERNEY');
        $sheet->setCellValue('BW62', 100);
        $sheet->setCellValue('D147', 'CC');
        $sheet->setCellValue('E147', '1144001122');
        $sheet->setCellValue('I147', 'PILOTO');
        $sheet->setCellValue('BW147', 50);

        $parsed = (new ParsePilaPlanillaService(new DetectPilaPlanillaLayoutService()))->parseSpreadsheet($book);

        $this->assertSame(1, $parsed['sheet_index']);
        $this->assertSame(20, $parsed['header_row']);
        $this->assertSame(5, $parsed['id_col']);
        $this->assertSame(9, $parsed['name_col']);
        $this->assertSame(75, $parsed['total_col']);
        $this->assertSame('BJ15', $parsed['valor_cell']);
        $this->assertSame('2026-08', $parsed['pension_period']);
        $this->assertSame('2026-09', $parsed['salud_period']);
        $this->assertSame(['1112956880', '1144001122'], array_map('strval', array_keys($parsed['groups'])));
        $this->assertSame([61, 62], $parsed['groups']['1112956880']['rows']);
        $this->assertSame(180500.0, $parsed['groups']['1112956880']['total']);
        $this->assertSame([147], $parsed['groups']['1144001122']['rows']);
    }
}
