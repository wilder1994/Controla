<?php

declare(strict_types=1);

namespace App\Services\Observatory;

use App\Models\Client;
use App\Models\ObservatoryReport;
use App\Models\ObservatoryReportType;
use App\Support\Observatory\ObservatoryReportTypeDefaults;
use Illuminate\Support\Str;

final class EnsureObservatoryReportTypesService
{
    public function execute(Client $client): void
    {
        if (ObservatoryReportType::query()->where('client_id', $client->id)->exists()) {
            $this->backfillReports($client);

            return;
        }

        foreach (ObservatoryReportTypeDefaults::rows() as $row) {
            ObservatoryReportType::query()->create([
                'client_id' => $client->id,
                'name' => $row['name'],
                'slug' => $row['slug'],
                'level' => $row['level'],
                'color' => $row['color'],
                'is_active' => true,
                'sort_order' => $row['sort_order'],
            ]);
        }

        $this->backfillReports($client);
    }

    public function resolve(Client $client, string $slug): ?ObservatoryReportType
    {
        $this->execute($client);

        return ObservatoryReportType::query()
            ->where('client_id', $client->id)
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();
    }

    /** @param  iterable<int>  $clientIds */
    public function optionsByClient(iterable $clientIds): array
    {
        $ids = collect($clientIds)->map(fn ($id): int => (int) $id)->filter()->unique()->values();
        foreach ($ids as $id) {
            $client = Client::query()->find($id);
            if ($client instanceof Client) {
                $this->execute($client);
            }
        }

        return ObservatoryReportType::query()
            ->whereIn('client_id', $ids->all())
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->groupBy('client_id')
            ->map(fn ($rows) => $rows->mapWithKeys(
                fn (ObservatoryReportType $type): array => [$type->slug => $type->name],
            )->all())
            ->all();
    }

    public function nextColor(Client $client): string
    {
        $used = ObservatoryReportType::query()
            ->where('client_id', $client->id)
            ->pluck('color')
            ->map(fn (string $color): string => strtolower($color))
            ->all();

        foreach (ObservatoryReportTypeDefaults::palette() as $color) {
            if (! in_array(strtolower($color), $used, true)) {
                return $color;
            }
        }

        return ObservatoryReportTypeDefaults::palette()[0];
    }

    public function uniqueSlug(Client $client, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'tipo';
        }

        $slug = $base;
        $i = 2;
        while (ObservatoryReportType::query()
            ->where('client_id', $client->id)
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    private function backfillReports(Client $client): void
    {
        $types = ObservatoryReportType::query()
            ->where('client_id', $client->id)
            ->get()
            ->keyBy('slug');

        ObservatoryReport::query()
            ->where('client_id', $client->id)
            ->whereNull('observatory_report_type_id')
            ->orderBy('id')
            ->each(function (ObservatoryReport $report) use ($types): void {
                $type = $types->get((string) $report->kind);
                if ($type instanceof ObservatoryReportType) {
                    $report->forceFill(['observatory_report_type_id' => $type->id])->save();
                }
            });
    }
}
