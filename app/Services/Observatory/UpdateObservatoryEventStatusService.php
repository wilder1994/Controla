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
    public function execute(ObservatoryEvent $event, ObservatoryEventStatus $status, User $actor): ObservatoryEvent
    {
        $current = $event->status instanceof ObservatoryEventStatus
            ? $event->status
            : ObservatoryEventStatus::tryFrom((string) $event->status);

        if ($current === null) {
            throw ValidationException::withMessages([
                'status' => 'El evento no tiene un estado válido.',
            ]);
        }

        if ($current === ObservatoryEventStatus::Cerrado) {
            throw ValidationException::withMessages([
                'status' => 'El evento ya está cerrado.',
            ]);
        }

        if ($current === $status) {
            return $event;
        }

        if (! $current->canTransitionTo($status)) {
            throw ValidationException::withMessages([
                'status' => 'Ese cambio de estado no está permitido.',
            ]);
        }

        return DB::transaction(function () use ($event, $current, $status, $actor): ObservatoryEvent {
            $event->status = $status;
            if ($status === ObservatoryEventStatus::Cerrado) {
                $event->closed_at = now();
                $event->closed_by_user_id = $actor->id;
            }

            $event->save();

            ObservatoryEventStatusLog::query()->create([
                'event_id' => $event->id,
                'from_status' => $current,
                'to_status' => $status,
                'user_id' => $actor->id,
                'created_at' => now(),
            ]);

            return $event->refresh();
        });
    }
}
