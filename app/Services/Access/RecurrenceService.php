<?php
namespace App\Services\Access;

use App\Models\AccessLog;
use App\Models\PreAuthorization;
use Illuminate\Support\Carbon;

class RecurrenceService
{
    public function isActiveOn(PreAuthorization $preAuthorization, Carbon $date): bool
    {
        if ($preAuthorization->recurrence === 'puntual') {
            return $date->toDateString() === $preAuthorization->scheduled_date?->toDateString();
        }

        if ($preAuthorization->valid_until !== null && $date->gt($preAuthorization->valid_until)) {
            return false;
        }

        return $this->matchesRecurrence($preAuthorization, $date);
    }

    public function matchesRecurrence(PreAuthorization $preAuthorization, Carbon $date): bool
    {
        $anchor = $preAuthorization->scheduled_date?->copy() ?? $date->copy();

        return match ($preAuthorization->recurrence) {
            'diario' => true,
            'semanal' => $anchor->dayOfWeek === $date->dayOfWeek,
            'bisemanal' => $anchor->dayOfWeek === $date->dayOfWeek && $anchor->diffInDays($date) % 14 === 0,
            'mensual' => $anchor->day === $date->day,
            default => false,
        };
    }

    public function entriesUsedOn(PreAuthorization $preAuthorization, Carbon $date): int
    {
        return AccessLog::query()
            ->where('qr_code', $preAuthorization->qr_code)
            ->whereDate('entry_time', $date)
            ->count();
    }

    public function entriesLeftOn(PreAuthorization $preAuthorization, Carbon $date): int
    {
        return max(0, (int) $preAuthorization->entries_per_day - $this->entriesUsedOn($preAuthorization, $date));
    }

    public function nextValidDate(PreAuthorization $preAuthorization, ?Carbon $from = null): ?Carbon
    {
        $from ??= today();

        if ($preAuthorization->recurrence === 'puntual') {
            $candidate = $preAuthorization->scheduled_date;

            return $candidate?->startOfDay()->gte($from->startOfDay()) ? $candidate : null;
        }

        for ($i = 0; $i < 370; $i++) {
            $candidate = $from->copy()->addDays($i);

            if ($preAuthorization->valid_until !== null && $candidate->gt($preAuthorization->valid_until)) {
                return null;
            }

            if ($this->matchesRecurrence($preAuthorization, $candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}