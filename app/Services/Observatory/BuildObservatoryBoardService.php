<?php

declare(strict_types=1);

namespace App\Services\Observatory;

use App\Enums\ObservatoryEventStatus;
use App\Enums\ObservatoryReportKind;
use App\Enums\ObservatoryReportSource;
use App\Models\Installation;
use App\Models\ObservatoryEvent;
use App\Models\ObservatoryReport;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

final class BuildObservatoryBoardService
{
    /**
     * @param  list<int>|null  $installationIds
     * @return array{
     *     total: int,
     *     nuevo: int,
     *     en_atencion: int,
     *     cerrado: int,
     *     closed_rate: int,
     *     top: list<array{name: string, client: ?string, count: int}>,
     *     trend: array{labels: list<string>, values: list<int>},
     *     kinds: array{labels: list<string>, values: list<int>},
     *     sources: array{labels: list<string>, values: list<int>}
     * }
     */
    public function execute(?int $companyId, ?int $clientId, ?array $installationIds, ?string $from, ?string $to): array
    {
        $query = $this->scoped($companyId, $clientId, $installationIds, $from, $to);

        $counts = (clone $query)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $total = (int) $counts->sum();
        $cerrado = (int) ($counts[ObservatoryEventStatus::Cerrado->value] ?? 0);

        return [
            'total' => $total,
            'nuevo' => (int) ($counts[ObservatoryEventStatus::Nuevo->value] ?? 0),
            'en_atencion' => (int) ($counts[ObservatoryEventStatus::EnAtencion->value] ?? 0),
            'cerrado' => $cerrado,
            'closed_rate' => $total === 0 ? 0 : (int) round(100 * $cerrado / $total),
            'top' => $this->ranking($query),
            'trend' => $this->trend($companyId, $clientId, $installationIds, $from, $to),
            'kinds' => $this->seriesByReport($query, 'kind', ObservatoryReportKind::cases()),
            'sources' => $this->seriesByReport($query, 'source', ObservatoryReportSource::cases()),
        ];
    }

    /**
     * @param  list<int>|null  $installationIds
     */
    public function scoped(?int $companyId, ?int $clientId, ?array $installationIds, ?string $from, ?string $to): Builder
    {
        return ObservatoryEvent::query()
            ->when($clientId !== null, fn (Builder $q) => $q->where('client_id', $clientId))
            ->when($companyId !== null, fn (Builder $q) => $q->whereHas(
                'client',
                fn (Builder $c) => $c->where('security_company_id', $companyId),
            ))
            ->when($installationIds !== null, fn (Builder $q) => $q->whereIn('installation_id', $installationIds))
            ->when($from !== null && $from !== '', fn (Builder $q) => $q->whereDate('opened_at', '>=', $from))
            ->when($to !== null && $to !== '', fn (Builder $q) => $q->whereDate('opened_at', '<=', $to));
    }

    /**
     * @return list<array{name: string, client: ?string, count: int}>
     */
    private function ranking(Builder $query): array
    {
        $ranked = (clone $query)
            ->selectRaw('installation_id, COUNT(*) as aggregate')
            ->groupBy('installation_id')
            ->orderByDesc('aggregate')
            ->limit(5)
            ->get();

        $sites = Installation::query()
            ->withoutGlobalScopes()
            ->with('client:id,name')
            ->whereIn('id', $ranked->pluck('installation_id')->all())
            ->get()
            ->keyBy('id');

        return $ranked->map(static function (ObservatoryEvent $row) use ($sites): array {
            $site = $sites->get((int) $row->installation_id);

            return [
                'name' => $site?->name ?? '—',
                'client' => $site?->client?->name,
                'count' => (int) $row->aggregate,
            ];
        })->all();
    }

    /**
     * @param  list<int>|null  $installationIds
     * @return array{labels: list<string>, values: list<int>}
     */
    private function trend(?int $companyId, ?int $clientId, ?array $installationIds, ?string $from, ?string $to): array
    {
        $end = ($to !== null && $to !== '') ? Carbon::parse($to)->startOfDay() : now()->startOfDay();
        $start = ($from !== null && $from !== '') ? Carbon::parse($from)->startOfDay() : $end->copy()->subDays(13);
        if ($start->greaterThan($end)) {
            [$start, $end] = [$end, $start];
        }

        $days = (int) $start->diffInDays($end);
        $weekly = $days > 45;
        $cursor = $weekly ? $start->copy()->startOfWeek() : $start->copy();
        $labels = [];
        $keys = [];

        while ($cursor->lte($end)) {
            if ($weekly) {
                $keys[] = $cursor->format('o-\WW');
                $labels[] = 'Sem '.$cursor->isoWeek();
                $cursor->addWeek();
            } else {
                $keys[] = $cursor->toDateString();
                $labels[] = $cursor->isoFormat('D MMM');
                $cursor->addDay();
            }
        }

        $raw = $this->scoped($companyId, $clientId, $installationIds, $start->toDateString(), $end->toDateString())
            ->selectRaw(
                $weekly
                    ? "DATE_FORMAT(opened_at, '%x-W%v') as bucket, COUNT(*) as aggregate"
                    : 'DATE(opened_at) as bucket, COUNT(*) as aggregate',
            )
            ->groupBy('bucket')
            ->pluck('aggregate', 'bucket');

        $values = [];
        foreach ($keys as $key) {
            $values[] = (int) ($raw[$key] ?? 0);
        }

        if ($labels === []) {
            return ['labels' => ['—'], 'values' => [0]];
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * @param  list<\BackedEnum>  $cases
     * @return array{labels: list<string>, values: list<int>}
     */
    private function seriesByReport(Builder $query, string $column, array $cases): array
    {
        $eventIds = (clone $query)->pluck('id');
        $raw = $eventIds->isEmpty()
            ? collect()
            : ObservatoryReport::query()
                ->whereIn('event_id', $eventIds->all())
                ->selectRaw($column.' as bucket, COUNT(*) as aggregate')
                ->groupBy('bucket')
                ->pluck('aggregate', 'bucket');

        $labels = [];
        $values = [];
        foreach ($cases as $case) {
            $labels[] = $case->label();
            $values[] = (int) ($raw[$case->value] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values];
    }
}
