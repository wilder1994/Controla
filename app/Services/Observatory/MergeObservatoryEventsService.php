<?php

declare(strict_types=1);

namespace App\Services\Observatory;

use App\Enums\ObservatoryEventStatus;
use App\Models\ObservatoryEvent;
use App\Models\ObservatoryReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class MergeObservatoryEventsService
{
    public function execute(ObservatoryEvent $keep, ObservatoryEvent $source): ObservatoryEvent
    {
        if ((int) $keep->id === (int) $source->id) {
            throw ValidationException::withMessages([
                'source_event_id' => 'Elige otro folio, no el mismo.',
            ]);
        }

        if ((int) $keep->client_id !== (int) $source->client_id
            || (int) $keep->installation_id !== (int) $source->installation_id) {
            throw ValidationException::withMessages([
                'source_event_id' => 'Solo se unen eventos del mismo colegio.',
            ]);
        }

        if ($keep->status === ObservatoryEventStatus::Cerrado) {
            throw ValidationException::withMessages([
                'source_event_id' => 'Este evento está cerrado. No se le pueden unir más reportes.',
            ]);
        }

        return DB::transaction(function () use ($keep, $source): ObservatoryEvent {
            $keep = ObservatoryEvent::query()->whereKey($keep->id)->lockForUpdate()->firstOrFail();
            $source = ObservatoryEvent::query()->whereKey($source->id)->lockForUpdate()->firstOrFail();

            ObservatoryReport::query()
                ->where('event_id', $source->id)
                ->update(['event_id' => $keep->id]);

            if ($source->opened_at !== null && ($keep->opened_at === null || $source->opened_at->lt($keep->opened_at))) {
                $keep->opened_at = $source->opened_at;
                $keep->save();
            }

            $source->delete();

            return $keep->refresh();
        });
    }
}
