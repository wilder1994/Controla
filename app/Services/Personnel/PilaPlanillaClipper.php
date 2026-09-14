<?php

declare(strict_types=1);

namespace App\Services\Personnel;

use RuntimeException;
use ZipArchive;

final class PilaPlanillaClipper
{
    /**
     * Copia el xlsx original (tema, media, fondo, dibujos) y deja encabezado + filas de un cotizante.
     *
     * @param  list<int>  $srcRows
     */
    public function writeClip(
        string $sourceXlsx,
        int $sheetIndex,
        int $headerRow,
        array $srcRows,
        string $valorCell,
        float $valorTotal,
        string $absoluteDest,
    ): void {
        if (! is_file($sourceXlsx)) {
            throw new RuntimeException('No está el Excel de origen para recortar.');
        }

        $directory = dirname($absoluteDest);
        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        if (! copy($sourceXlsx, $absoluteDest)) {
            throw new RuntimeException('No se pudo copiar la plantilla original.');
        }

        $zip = new ZipArchive;
        if ($zip->open($absoluteDest) !== true) {
            throw new RuntimeException('No se pudo abrir el recorte.');
        }

        try {
            $sheetPath = $this->worksheetZipPath($zip, $sheetIndex);
            $xml = $zip->getFromName($sheetPath);
            if (! is_string($xml) || $xml === '') {
                throw new RuntimeException('No se leyó la hoja de cotizantes.');
            }

            $clipped = $this->clipSheetXml($xml, $headerRow, $srcRows, $valorCell, $valorTotal);
            $zip->deleteName($sheetPath);
            $zip->addFromString($sheetPath, $clipped);
        } finally {
            $zip->close();
        }
    }

    /**
     * @param  list<int>  $srcRows
     */
    private function clipSheetXml(string $xml, int $headerRow, array $srcRows, string $valorCell, float $valorTotal): string
    {
        $map = [];
        $destRow = $headerRow + 1;
        foreach ($srcRows as $src) {
            $src = (int) $src;
            if ($src > $headerRow && ! isset($map[$src])) {
                $map[$src] = $destRow++;
            }
        }

        if (preg_match('/^(.*?<sheetData\b[^>]*>)(.*)(<\/sheetData>.*)$/is', $xml, $parts) !== 1) {
            throw new RuntimeException('La hoja no tiene sheetData.');
        }

        preg_match_all('/<row\b[^>]*\/>|<row\b[^>]*>.*?<\/row>/is', $parts[2], $rowMatches);
        $kept = [];
        foreach ($rowMatches[0] as $rowXml) {
            if (preg_match('/\br="(\d+)"/', $rowXml, $rowNum) !== 1) {
                continue;
            }
            $row = (int) $rowNum[1];
            if ($row <= $headerRow) {
                $kept[] = $rowXml;

                continue;
            }
            if (! isset($map[$row])) {
                continue;
            }
            $kept[] = $this->remapRowXml($rowXml, $row, $map[$row]);
        }

        $xml = $parts[1].implode('', $kept).$parts[3];
        $xml = $this->clipMerges($xml, $headerRow, $map);

        return $this->replaceNumericCell($xml, $valorCell, $valorTotal);
    }

    private function remapRowXml(string $rowXml, int $from, int $to): string
    {
        $rowXml = preg_replace('/(<row\b[^>]*\br=")' . $from . '(")/', '${1}'.$to.'${2}', $rowXml, 1) ?? $rowXml;
        $rowXml = preg_replace('/\br="([A-Z]{1,3})' . $from . '"/', 'r="${1}'.$to.'"', $rowXml) ?? $rowXml;
        $rowXml = preg_replace('/<f\b[^>]*>.*?<\/f>/is', '', $rowXml) ?? $rowXml;

        return $rowXml;
    }

    /**
     * @param  array<int, int>  $map
     */
    private function clipMerges(string $xml, int $headerRow, array $map): string
    {
        if (preg_match('/<mergeCells\b[^>]*>(.*?)<\/mergeCells>/is', $xml, $block) !== 1) {
            return $xml;
        }

        preg_match_all('/<mergeCell\b[^>]*\/>/i', $block[1], $cells);
        $kept = [];
        foreach ($cells[0] as $cell) {
            if (preg_match('/\bref="([^"]+)"/', $cell, $ref) !== 1) {
                continue;
            }
            $next = $this->remapMergeRef($ref[1], $headerRow, $map);
            if ($next === null) {
                continue;
            }
            $kept[] = '<mergeCell ref="'.$next.'"/>';
        }

        $replacement = $kept === []
            ? ''
            : '<mergeCells count="'.count($kept).'">'.implode('', $kept).'</mergeCells>';

        return preg_replace('/<mergeCells\b[^>]*>.*?<\/mergeCells>/is', $replacement, $xml, 1) ?? $xml;
    }

    /**
     * @param  array<int, int>  $map
     */
    private function remapMergeRef(string $ref, int $headerRow, array $map): ?string
    {
        if (preg_match('/^([A-Z]+)(\d+):([A-Z]+)(\d+)$/i', $ref, $m) !== 1) {
            return null;
        }

        $startRow = (int) $m[2];
        $endRow = (int) $m[4];
        if ($startRow <= $headerRow && $endRow <= $headerRow) {
            return strtoupper($m[1]).$startRow.':'.strtoupper($m[3]).$endRow;
        }

        if ($startRow === $endRow && isset($map[$startRow])) {
            $row = $map[$startRow];

            return strtoupper($m[1]).$row.':'.strtoupper($m[3]).$row;
        }

        if (isset($map[$startRow], $map[$endRow])) {
            return strtoupper($m[1]).$map[$startRow].':'.strtoupper($m[3]).$map[$endRow];
        }

        return null;
    }

    private function replaceNumericCell(string $xml, string $address, float $value): string
    {
        $address = strtoupper($address);
        $cell = '<c r="'.$address.'"><v>'.$this->xmlNumber($value).'</v></c>';
        $replaced = preg_replace(
            '/<c\b[^>]*\br="'.$address.'"[^>]*>.*?<\/c>/is',
            $cell,
            $xml,
            1,
            $count,
        );

        return is_string($replaced) && $count > 0 ? $replaced : $xml;
    }

    private function xmlNumber(float $value): string
    {
        if (abs($value - round($value)) < 0.0000001) {
            return (string) (int) round($value);
        }

        return rtrim(rtrim(sprintf('%.8F', $value), '0'), '.');
    }

    private function worksheetZipPath(ZipArchive $zip, int $sheetIndex): string
    {
        $workbook = $this->zipRequired($zip, 'xl/workbook.xml');
        if (preg_match_all('/<sheet\b[^>]*>/i', $workbook, $sheets) !== false && $sheets[0] === []) {
            throw new RuntimeException('El libro no tiene hojas.');
        }
        if (! isset($sheets[0][$sheetIndex])) {
            throw new RuntimeException('No está la hoja de cotizantes.');
        }

        $tag = $sheets[0][$sheetIndex];
        if (preg_match('/(?:r:id|r:Id)\s*=\s*"([^"]+)"/', $tag, $rid) !== 1) {
            throw new RuntimeException('La hoja no tiene relación.');
        }

        $rels = $this->zipRequired($zip, 'xl/_rels/workbook.xml.rels');
        $id = preg_quote($rid[1], '/');
        if (preg_match('/<Relationship\b[^>]*\bId="'.$id.'"[^>]*\bTarget="([^"]+)"/i', $rels, $target) !== 1
            && preg_match('/<Relationship\b[^>]*\bTarget="([^"]+)"[^>]*\bId="'.$id.'"/i', $rels, $target) !== 1) {
            throw new RuntimeException('No está el XML de la hoja.');
        }

        $path = ltrim(str_replace('\\', '/', html_entity_decode($target[1])), '/');
        if (str_starts_with($path, 'xl/')) {
            return $path;
        }

        return 'xl/'.$path;
    }

    private function zipRequired(ZipArchive $zip, string $name): string
    {
        $xml = $zip->getFromName($name);
        if (is_string($xml) && $xml !== '') {
            return $xml;
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = str_replace('\\', '/', (string) $zip->getNameIndex($i));
            if (strcasecmp($entry, $name) === 0) {
                $xml = $zip->getFromIndex($i);

                return is_string($xml) ? $xml : '';
            }
        }

        throw new RuntimeException('Falta '.$name.' en el Excel.');
    }
}
