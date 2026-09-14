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

        $parts = $this->splitSheetData($xml);
        $kept = [];
        foreach ($this->rowChunks($parts['body']) as $rowXml) {
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

        $xml = $parts['prefix'].implode('', $kept).$parts['suffix'];
        $xml = $this->clipMerges($xml, $headerRow, $map);

        return $this->replaceNumericCell($xml, $valorCell, $valorTotal);
    }

    /**
     * @return array{prefix: string, body: string, suffix: string}
     */
    private function splitSheetData(string $xml): array
    {
        if (preg_match('/<([a-zA-Z0-9]+:)?sheetData\b[^>]*>/i', $xml, $open, PREG_OFFSET_CAPTURE) !== 1) {
            throw new RuntimeException('La hoja no tiene sheetData.');
        }

        $openTag = $open[0][0];
        $openAt = (int) $open[0][1];
        $prefixEnd = $openAt + strlen($openTag);
        if (str_contains(rtrim($openTag), '/>')) {
            return [
                'prefix' => substr($xml, 0, $prefixEnd),
                'body' => '',
                'suffix' => substr($xml, $prefixEnd),
            ];
        }

        $prefixNs = $open[1][0] ?? '';
        $closeTag = '</'.$prefixNs.'sheetData>';
        $closeAt = stripos($xml, $closeTag, $prefixEnd);
        if ($closeAt === false) {
            $closeTag = '</sheetData>';
            $closeAt = stripos($xml, $closeTag, $prefixEnd);
        }
        if ($closeAt === false) {
            throw new RuntimeException('La hoja no tiene sheetData.');
        }

        return [
            'prefix' => substr($xml, 0, $prefixEnd),
            'body' => substr($xml, $prefixEnd, $closeAt - $prefixEnd),
            'suffix' => substr($xml, $closeAt),
        ];
    }

    /** @return list<string> */
    private function rowChunks(string $body): array
    {
        $rows = [];
        $pos = 0;
        $length = strlen($body);
        while ($pos < $length) {
            $start = stripos($body, '<row', $pos);
            if ($start === false) {
                break;
            }
            $after = $body[$start + 4] ?? '';
            if ($after !== ' ' && $after !== '>' && $after !== '/') {
                $pos = $start + 4;

                continue;
            }
            $gt = strpos($body, '>', $start);
            if ($gt === false) {
                break;
            }
            $open = substr($body, $start, $gt - $start + 1);
            if (str_contains($open, '/>')) {
                $rows[] = $open;
                $pos = $gt + 1;

                continue;
            }
            $end = stripos($body, '</row>', $gt);
            if ($end === false) {
                break;
            }
            $rows[] = substr($body, $start, $end + 6 - $start);
            $pos = $end + 6;
        }

        return $rows;
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
        if (preg_match('/<([a-zA-Z0-9]+:)?mergeCells\b[^>]*>/i', $xml, $open, PREG_OFFSET_CAPTURE) !== 1) {
            return $xml;
        }

        $openAt = (int) $open[0][1];
        $openTag = $open[0][0];
        if (str_contains(rtrim($openTag), '/>')) {
            return $xml;
        }

        $ns = $open[1][0] ?? '';
        $closeTag = '</'.$ns.'mergeCells>';
        $closeAt = stripos($xml, $closeTag, $openAt + strlen($openTag));
        if ($closeAt === false) {
            $closeTag = '</mergeCells>';
            $closeAt = stripos($xml, $closeTag, $openAt + strlen($openTag));
        }
        if ($closeAt === false) {
            return $xml;
        }

        $inner = substr($xml, $openAt + strlen($openTag), $closeAt - $openAt - strlen($openTag));
        $kept = [];
        $pos = 0;
        while (($refAt = stripos($inner, 'ref="', $pos)) !== false) {
            $from = $refAt + 5;
            $to = strpos($inner, '"', $from);
            if ($to === false) {
                break;
            }
            $next = $this->remapMergeRef(substr($inner, $from, $to - $from), $headerRow, $map);
            if ($next !== null) {
                $kept[] = '<mergeCell ref="'.$next.'"/>';
            }
            $pos = $to + 1;
        }

        $replacement = $kept === []
            ? ''
            : '<mergeCells count="'.count($kept).'">'.implode('', $kept).'</mergeCells>';

        return substr($xml, 0, $openAt).$replacement.substr($xml, $closeAt + strlen($closeTag));
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
