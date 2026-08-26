<?php

declare(strict_types=1);

namespace App\Services\Company;

use App\Domain\Supervision\Data\SupervisionPeriodSnapshot;
use Illuminate\Support\Str;
use PhpOffice\PhpPresentation\DocumentLayout;
use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\PhpPresentation;
use PhpOffice\PhpPresentation\Shape\Chart\Legend;
use PhpOffice\PhpPresentation\Shape\Chart\Series;
use PhpOffice\PhpPresentation\Shape\Chart\Type\Bar;
use PhpOffice\PhpPresentation\Slide;
use PhpOffice\PhpPresentation\Slide\Background\Color as BackgroundColor;
use PhpOffice\PhpPresentation\Style\Color;
use PhpOffice\PhpPresentation\Style\Fill;

final class ExportSupervisionExecutiveReportService
{
    private const NAVY = 'FF0F172A';

    private const CARD = 'FF1E293B';

    private const GOLD = 'FFFBBF24';

    private const CYAN = 'FF38BDF8';

    private const INK = 'FFF8FAFC';

    private const MUTED = 'FF94A3B8';

    /**
     * @return array{path: string, filename: string}
     */
    public function execute(SupervisionPeriodSnapshot $snapshot): array
    {
        $ppt = new PhpPresentation;
        $ppt->getLayout()->setDocumentLayout(DocumentLayout::LAYOUT_SCREEN_16X9, true);
        $ppt->getDocumentProperties()
            ->setCreator('Controla')
            ->setLastModifiedBy('Controla')
            ->setTitle('Informe de Supervisión')
            ->setSubject($snapshot->caption)
            ->setDescription('Informe gerencial de Supervisión de campo');

        $this->coverSlide($ppt->getActiveSlide(), $snapshot);
        $this->kpiSlide($this->slide($ppt), $snapshot);
        $this->modulesSlide($this->slide($ppt), $snapshot);
        $this->chartSlide(
            $this->slide($ppt),
            'Actividad por supervisor',
            $this->groupedBar(
                $this->namedValues($snapshot->bySupervisor, 'reviews'),
                $this->namedValues($snapshot->bySupervisor, 'logs'),
                'Revistas',
                'Registros de campo',
            ),
        );
        $this->sitesSlide($this->slide($ppt), $snapshot);
        $this->alertsSlide($this->slide($ppt), $snapshot->alerts);

        return [
            'path' => $this->save($ppt),
            'filename' => $this->fileName($snapshot),
        ];
    }

    public function fileName(SupervisionPeriodSnapshot $snapshot): string
    {
        $empresa = Str::slug($snapshot->companyName) ?: 'empresa';

        return sprintf(
            'Informe_Supervision_%s_%s_%s.pptx',
            $empresa,
            $snapshot->from,
            $snapshot->to,
        );
    }

    private function coverSlide(Slide $slide, SupervisionPeriodSnapshot $snapshot): void
    {
        $this->paintSlide($slide);
        $this->text($slide, 'CONTROLA', 40, 140, 880, 28, 14, self::GOLD, true);
        $this->text($slide, 'Informe gerencial de Supervisión', 40, 176, 880, 48, 28, self::INK, true);
        $this->text($slide, $snapshot->companyName, 40, 236, 880, 32, 16, self::CYAN);
        $this->text($slide, $snapshot->caption, 40, 280, 880, 28, 14, self::MUTED);
        $this->text($slide, 'Generado el '.now()->format('d/m/Y H:i'), 40, 460, 880, 24, 12, self::MUTED);
    }

    private function kpiSlide(Slide $slide, SupervisionPeriodSnapshot $snapshot): void
    {
        $this->heading($slide, 'Cobertura y semáforo', $snapshot->caption);

        $coverage = $snapshot->coveragePercent !== null
            ? number_format($snapshot->coveragePercent, 1).'%'
            : '—';
        $fill = match ($snapshot->semaphore) {
            'green' => 'FF34D399',
            'yellow' => 'FFFBBF24',
            'red' => 'FFF87171',
            default => self::CARD,
        };
        $ink = $snapshot->semaphore === 'neutral' ? self::INK : self::NAVY;

        $boxes = [
            [$coverage, 'Cobertura de sitios', $fill, $ink],
            [(string) $snapshot->reviews, 'Revistas', self::CARD, self::INK],
            [(string) $snapshot->fieldLogs, 'Registros de campo', self::CARD, self::INK],
            [(string) $snapshot->kmTraveled, 'Km recorridos', self::CARD, self::GOLD],
            [(string) $snapshot->recommendations['open'], 'Recs. abiertas', self::CARD, self::INK],
        ];

        foreach ($boxes as $index => [$value, $label, $boxFill, $boxInk]) {
            $shape = $slide->createRichTextShape()
                ->setOffsetX(40 + ($index * 184))
                ->setOffsetY(120)
                ->setWidth(172)
                ->setHeight(130);
            $shape->setFill($this->solid($boxFill));
            $valueRun = $shape->createTextRun($value);
            $valueRun->getFont()->setName('Calibri')->setBold(true)->setSize(22)->setColor(new Color($boxInk));
            $shape->createBreak();
            $labelRun = $shape->createTextRun(mb_strtoupper($label));
            $labelRun->getFont()->setName('Calibri')->setSize(10)->setColor(new Color($boxInk));
        }

        $detail = $snapshot->sitesContracted > 0
            ? 'Sitios visitados '.$snapshot->sitesVisited.' de '.$snapshot->sitesContracted.'. Umbral verde ≥ 90 %, amarillo ≥ 70 %.'
            : 'No hay sitios con línea de Supervisión en este periodo.';
        $this->text($slide, $detail, 40, 280, 880, 50, 14, self::MUTED);
        $this->text(
            $slide,
            'Revistas de portería (código Accesos) no entran en este informe. Solo captura de campo.',
            40,
            340,
            880,
            40,
            12,
            self::MUTED,
        );
    }

    private function modulesSlide(Slide $slide, SupervisionPeriodSnapshot $snapshot): void
    {
        $this->heading($slide, 'Ocho módulos de campo', 'Revista + siete registros operativos');

        $index = 0;
        foreach ($snapshot->modules as $row) {
            $col = $index % 4;
            $rowN = intdiv($index, 4);
            $shape = $slide->createRichTextShape()
                ->setOffsetX(40 + ($col * 230))
                ->setOffsetY(110 + ($rowN * 180))
                ->setWidth(214)
                ->setHeight(160);
            $shape->setFill($this->solid(self::CARD));
            $title = $shape->createTextRun($row['label']);
            $title->getFont()->setName('Calibri')->setBold(true)->setSize(13)->setColor(new Color(self::GOLD));
            $shape->createBreak();
            $total = $shape->createTextRun((string) $row['total']);
            $total->getFont()->setName('Calibri')->setBold(true)->setSize(28)->setColor(new Color(self::INK));
            $shape->createBreak();
            $detail = $shape->createTextRun(
                'OK '.$row['ok'].' · Atención '.$row['attention'].' · Crítico '.$row['critical']
            );
            $detail->getFont()->setName('Calibri')->setSize(10)->setColor(new Color(self::MUTED));
            $index++;
        }
    }

    private function sitesSlide(Slide $slide, SupervisionPeriodSnapshot $snapshot): void
    {
        $this->heading($slide, 'Sitios y recomendaciones', $snapshot->caption);

        $unvisited = $snapshot->unvisitedSites === []
            ? 'Todos los sitios con Supervisión tienen al menos una revista en el periodo.'
            : 'Sin revista: '.implode(', ', array_slice($snapshot->unvisitedSites, 0, 12));
        $this->text($slide, $unvisited, 40, 110, 880, 80, 14, self::INK);

        $recs = 'Recomendaciones — abiertas '.$snapshot->recommendations['open']
            .', en proceso '.$snapshot->recommendations['progress']
            .', cerradas '.$snapshot->recommendations['closed']
            .', vencidas '.$snapshot->recommendations['overdue'].'.';
        $this->text($slide, $recs, 40, 210, 880, 60, 14, self::CYAN);

        $clients = [];
        foreach (array_slice($snapshot->byClient, 0, 8) as $row) {
            $clients[] = $row['name'].' ('.$row['reviews'].' revistas, '.$row['attention'].' atenciones)';
        }
        $this->text(
            $slide,
            $clients === [] ? 'Sin actividad por sitio.' : implode(' · ', $clients),
            40,
            290,
            880,
            140,
            13,
            self::MUTED,
        );
    }

    /**
     * @param  list<string>  $alerts
     */
    private function alertsSlide(Slide $slide, array $alerts): void
    {
        $this->heading($slide, 'Alertas del periodo', 'Cobertura, alarmas, documentos de puesto y recomendaciones');

        $shape = $slide->createRichTextShape()
            ->setOffsetX(40)
            ->setOffsetY(110)
            ->setWidth(880)
            ->setHeight(380);
        $shape->setFill($this->solid(self::CARD));

        foreach ($alerts as $index => $alert) {
            if ($index > 0) {
                $shape->createBreak();
                $shape->createBreak();
            }
            $run = $shape->createTextRun('•  '.$alert);
            $run->getFont()->setName('Calibri')->setSize(15)->setColor(new Color(self::INK));
        }
    }

    private function chartSlide(Slide $slide, string $title, Bar $type): void
    {
        $this->heading($slide, $title, 'Gráfico nativo editable en PowerPoint');

        $chart = $slide->createChartShape();
        $chart->setResizeProportional(false)
            ->setIncludeSpreadsheet(true)
            ->setOffsetX(40)
            ->setOffsetY(100)
            ->setWidth(880)
            ->setHeight(400);
        $chart->getTitle()->setVisible(false);
        $chart->getLegend()->setPosition(Legend::POSITION_BOTTOM);
        $chart->getPlotArea()->setType($type);
    }

    /**
     * @param  array<string, int>  $executed
     * @param  array<string, int>  $target
     */
    private function groupedBar(array $executed, array $target, string $executedTitle, string $targetTitle): Bar
    {
        $bar = new Bar;
        $bar->setBarDirection(Bar::DIRECTION_VERTICAL);
        $bar->setBarGrouping(Bar::GROUPING_CLUSTERED);
        $bar->addSeries($this->series($executedTitle, $executed, self::CYAN));
        $bar->addSeries($this->series($targetTitle, $target, self::GOLD));

        return $bar;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, int>
     */
    private function namedValues(array $rows, string $key): array
    {
        $values = [];
        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $values[$name] = (int) ($row[$key] ?? 0);
        }

        return $values;
    }

    /**
     * @param  array<string, int>  $values
     */
    private function series(string $title, array $values, string $color): Series
    {
        $series = new Series($title, $this->chartValues($values));
        $series->setShowSeriesName(false);
        $series->setShowValue(true);
        $series->setFill($this->solid($color));
        $series->getFont()->setName('Calibri')->setSize(9)->setColor(new Color(self::NAVY));

        return $series;
    }

    /**
     * @param  array<string, int>  $values
     * @return array<string, string>
     */
    private function chartValues(array $values): array
    {
        if ($values === []) {
            return ['Sin datos' => '0'];
        }

        $mapped = [];
        foreach ($values as $label => $value) {
            $mapped[$label] = (string) $value;
        }

        return $mapped;
    }

    private function slide(PhpPresentation $ppt): Slide
    {
        $slide = $ppt->createSlide();
        $this->paintSlide($slide);

        return $slide;
    }

    private function paintSlide(Slide $slide): void
    {
        $background = new BackgroundColor;
        $background->setColor(new Color(self::NAVY));
        $slide->setBackground($background);

        $bar = $slide->createRichTextShape()
            ->setOffsetX(0)
            ->setOffsetY(0)
            ->setWidth(960)
            ->setHeight(8);
        $bar->setFill($this->solid(self::GOLD));
    }

    private function heading(Slide $slide, string $title, string $caption): void
    {
        $this->text($slide, $title, 40, 24, 880, 40, 22, self::INK, true);
        $this->text($slide, $caption, 40, 64, 880, 28, 12, self::MUTED);
    }

    private function text(
        Slide $slide,
        string $content,
        int $x,
        int $y,
        int $width,
        int $height,
        int $size,
        string $color,
        bool $bold = false,
    ): void {
        $shape = $slide->createRichTextShape()
            ->setOffsetX($x)
            ->setOffsetY($y)
            ->setWidth($width)
            ->setHeight($height);
        $run = $shape->createTextRun($content);
        $run->getFont()
            ->setName('Calibri')
            ->setSize($size)
            ->setBold($bold)
            ->setColor(new Color($color));
    }

    private function solid(string $argb): Fill
    {
        $fill = new Fill;
        $fill->setFillType(Fill::FILL_SOLID)->setStartColor(new Color($argb));

        return $fill;
    }

    private function save(PhpPresentation $ppt): string
    {
        $path = tempnam(sys_get_temp_dir(), 'controla_sup_');
        if ($path === false) {
            throw new \RuntimeException('No se pudo crear el archivo temporal del informe.');
        }

        unlink($path);
        $path .= '.pptx';
        IOFactory::createWriter($ppt, 'PowerPoint2007')->save($path);

        return $path;
    }
}
