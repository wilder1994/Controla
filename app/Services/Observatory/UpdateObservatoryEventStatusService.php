<?php

declare(strict_types=1);

namespace App\Services\Observatory;

use App\Enums\ObservatoryEventStatus;
use App\Models\ObservatoryEvent;
use App\Models\ObservatoryEventStatusLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UpdateObservatoryEventStatusService
{
    public function execute(
        ObservatoryEvent $event,
        ObservatoryEventStatus $status,
        User $actor,
        string $note,
    ): ObservatoryEvent {
        $current = $event->status instanceof ObservatoryEventStatus
            ? $event->status
            : ObservatoryEventStatus::tryFrom((string) $event->status);

        if ($current === null) {
            throw ValidationException::withMessages([
                'status' => 'El evento no tiene un estado válido.',
            ]);
        }

        $note = trim($note);
        if (mb_strlen($note) < 10) {
            throw ValidationException::withMessages([
                'note' => 'La observación es obligatoria (mínimo 10 caracteres).',
            ]);
        }

        if ($current === ObservatoryEventStatus::Cerrado) {
            throw ValidationException::withMessages([
                'status' => 'El evento ya está cerrado.',
            ]);
        }

        if ($current === $status) {
            if ($current !== ObservatoryEventStatus::EnAtencion) {
                throw ValidationException::withMessages([
                    'status' => 'Solo se puede agregar una observación con el folio en atención.',
                ]);
            }

            return $this->writeLog($event, $current, $status, $actor, $note);
        }

        if (! $current->canTransitionTo($status)) {
            throw ValidationException::withMessages([
                'status' => 'Ese cambio de estado no está permitido.',
            ]);
        }

        return DB::transaction(function () use ($event, $current, $status, $actor, $note): ObservatoryEvent {
            $event->status = $status;
            if ($status === ObservatoryEventStatus::Cerrado) {
                $event->closed_at = now();
                $event->closed_by_user_id = $actor->id;
            }

            $event->save();
            $this->writeLog($event, $current, $status, $actor, $note);

            return $event->refresh();
        });
    }

    private function writeLog(
        ObservatoryEvent $event,
        ObservatoryEventStatus $from,
        ObservatoryEventStatus $to,
        User $actor,
        string $note,
    ): ObservatoryEvent {
        ObservatoryEventStatusLog::query()->create([
            'event_id' => $event->id,
            'from_status' => $from,
            'to_status' => $to,
            'user_id' => $actor->id,
            'note' => $note,
            'created_at' => now(),
        ]);

        return $event->refresh();
    }
}
