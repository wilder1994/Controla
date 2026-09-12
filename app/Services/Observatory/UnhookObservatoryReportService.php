<?php

declare(strict_types=1);

namespace App\Services\Observatory;

use App\Enums\ObservatoryEventStatus;
use App\Models\ObservatoryEvent;
use App\Models\ObservatoryReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UnhookObservatoryReportService
{
    public function execute(ObservatoryEvent $event, ObservatoryReport $report): ObservatoryEvent
    {
        if ((int) $report->event_id !== (int) $event->id) {
            throw ValidationException::withMessages([
                'report' => 'Ese reporte no pertenece a este evento.',
            ]);
        }

        return DB::transaction(function () use ($event, $report): ObservatoryEvent {
            $locked = ObservatoryEvent::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();
            $count = ObservatoryReport::query()->where('event_id', $locked->id)->count();

            if ($count < 2) {
                throw ValidationException::withMessages([
                    'report' => 'No se puede sacar el único reporte. El evento quedaría vacío.',
                ]);
            }

            $fresh = ObservatoryReport::query()->whereKey($report->id)->lockForUpdate()->firstOrFail();

            $created = ObservatoryEvent::query()->create([
                'client_id' => $locked->client_id,
                'installation_id' => $locked->installation_id,
                'status' => ObservatoryEventStatus::Nuevo,
                'title' => $fresh->kindLabel(),
                'opened_at' => $fresh->created_at ?? now(),
            ]);

            $fresh->event_id = $created->id;
            $fresh->save();

            return $created->refresh();
        });
    }
}
