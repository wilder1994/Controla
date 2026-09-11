<?php

declare(strict_types=1);

namespace App\Services\Observatory;

use App\Enums\InstallationKind;
use App\Enums\ObservatoryEventStatus;
use App\Models\Installation;
use App\Models\ObservatoryEvent;
use App\Models\ObservatoryReport;

final class BuildObservatoryMapService
{
    /**
     * @param  list<int>|null  $installationIds
     * @return array{google_maps: array{api_key: ?string, center: mixed, zoom: mixed}, sites: list<array<string, mixed>>, points: list<array<string, mixed>>}
     */
    public function execute(?int $companyId, ?int $clientId, ?array $installationIds, string $eventShowRoute): array
    {
        $sites = Installation::query()
            ->withoutGlobalScopes()
            ->with(['client:id,name,security_company_id', 'observatoryEvents.reports'])
            ->where('kind', InstallationKind::Colegio->value)
            ->where('is_active', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when($clientId !== null, fn ($q) => $q->where('client_id', $clientId))
            ->when($companyId !== null, fn ($q) => $q->whereHas(
                'client',
                fn ($c) => $c->where('security_company_id', $companyId),
            ))
            ->when($installationIds !== null, fn ($q) => $q->whereIn('id', $installationIds))
            ->orderBy('name')
            ->get();

        return [
            'google_maps' => [
                'api_key' => config('google-maps.api_key'),
                'center' => config('google-maps.default_center'),
                'zoom' => config('google-maps.default_zoom'),
            ],
            'sites' => $sites->map(function (Installation $site) use ($eventShowRoute): array {
                $events = $site->observatoryEvents;
                $open = $events->filter(
                    fn (ObservatoryEvent $event): bool => $event->status !== ObservatoryEventStatus::Cerrado,
                );
                $status = $this->worstStatus($events);

                $focus = $open->sortByDesc('id')->first() ?? $events->sortByDesc('id')->first();

                return [
                    'id' => (int) $site->id,
                    'name' => $site->name,
                    'client' => $site->client?->name,
                    'dane_code' => $site->dane_code,
                    'lat' => (float) $site->latitude,
                    'lng' => (float) $site->longitude,
                    'open_count' => $open->count(),
                    'status' => $status?->value,
                    'status_label' => $status?->label() ?? 'Sin reportes',
                    'show_url' => $focus instanceof ObservatoryEvent
                        ? route($eventShowRoute, $focus)
                        : null,
                    'heat_weight' => max(1, $open->count()),
                ];
            })->values()->all(),
            'points' => $sites->flatMap(function (Installation $site) use ($eventShowRoute) {
                return $site->observatoryEvents->flatMap(function (ObservatoryEvent $event) use ($site, $eventShowRoute) {
                    return $event->reports->map(function (ObservatoryReport $report) use ($site, $event, $eventShowRoute): ?array {
                        $lat = $report->latitude ?? $site->latitude;
                        $lng = $report->longitude ?? $site->longitude;
                        if ($lat === null || $lng === null) {
                            return null;
                        }

                        return [
                            'lat' => (float) $lat,
                            'lng' => (float) $lng,
                            'open' => $event->status !== ObservatoryEventStatus::Cerrado,
                            'status' => $event->status->value,
                            'status_label' => $event->statusLabel(),
                            'title' => $site->name,
                            'kind' => $report->kindLabel(),
                            'show_url' => route($eventShowRoute, $event),
                        ];
                    })->filter();
                });
            })->values()->all(),
        ];
    }

    /** @param  \Illuminate\Support\Collection<int, ObservatoryEvent>  $events */
    private function worstStatus($events): ?ObservatoryEventStatus
    {
        if ($events->contains(fn (ObservatoryEvent $e): bool => $e->status === ObservatoryEventStatus::Nuevo)) {
            return ObservatoryEventStatus::Nuevo;
        }
        if ($events->contains(fn (ObservatoryEvent $e): bool => $e->status === ObservatoryEventStatus::EnAtencion)) {
            return ObservatoryEventStatus::EnAtencion;
        }
        if ($events->contains(fn (ObservatoryEvent $e): bool => $e->status === ObservatoryEventStatus::Cerrado)) {
            return ObservatoryEventStatus::Cerrado;
        }

        return null;
    }
}
