<?php

declare(strict_types=1);

namespace App\Exports;

use App\Support\Client\ClientExcelSchema;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class ClientImportTemplateDataSheet implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    public function title(): string
    {
        return ClientExcelSchema::DATA_SHEET;
    }

    /** @return list<string> */
    public function headings(): array
    {
        return ClientExcelSchema::headers();
    }

    /** @return list<list<string>> */
    public function array(): array
    {
        return [];
    }

    public function styles(Worksheet $sheet): array
    {
        $last = count(ClientExcelSchema::headers());
        $lastAddress = ClientExcelSchema::cellAddress($last, 1);
        $sheet->freezePane('A2');
        $sheet->getRowDimension(1)->setRowHeight(28);
        $sheet->getStyle('A1:'.$lastAddress)
            ->getAlignment()
            ->setWrapText(true)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        for ($column = 1; $column <= $last; $column++) {
            $rgb = ClientExcelSchema::headerFillRgb($column);
            $sheet->getStyle(ClientExcelSchema::cellAddress($column, 1))->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => $rgb === ClientExcelSchema::FILL_RED ? 'FFFFFF' : '1E293B'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $rgb],
                ],
            ]);
        }

        return [];
    }
}
