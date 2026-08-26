<?php

namespace App\Services\Access;

use App\Models\CommonZone;
use App\Models\CommonZoneBooking;
use Illuminate\Support\Str;

class ZoneBookingService
{
    public function conflicts(CommonZoneBooking $booking): bool
    {
        return CommonZoneBooking::query()
            ->where('common_zone_id', $booking->common_zone_id)
            ->where('date', $booking->date)
            ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
            ->where('id', '!=', $booking->id ?? 0)
            ->where(function ($query) use ($booking): void {
                $query->whereBetween('start_time', [$booking->start_time, $booking->end_time])
                    ->orWhereBetween('end_time', [$booking->start_time, $booking->end_time])
                    ->orWhere(function ($query) use ($booking): void {
                        $query->where('start_time', '<=', $booking->start_time)
                            ->where('end_time', '>=', $booking->end_time);
                    });
            })
            ->exists();
    }

    public function capacityExceeded(CommonZone $zone, CommonZoneBooking $booking): bool
    {
        $current = CommonZoneBooking::query()
            ->where('common_zone_id', $zone->id)
            ->where('date', $booking->date)
            ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
            ->where('id', '!=', $booking->id ?? 0)
            ->where('start_time', '<', $booking->end_time)
            ->where('end_time', '>', $booking->start_time)
            ->sum('people_count');

        return $zone->capacity > 0 && ($current + $booking->people_count) > $zone->capacity;
    }

    public function withinSchedule(CommonZone $zone, CommonZoneBooking $booking): bool
    {
        if ($booking->start_time >= $booking->end_time) {
            return false;
        }

        $open = $zone->open_time?->format('H:i') ?? '06:00';
        $close = $zone->close_time?->format('H:i') ?? '22:00';

        return $booking->start_time >= $open && $booking->end_time <= $close;
    }

    public function validate(CommonZone $zone, CommonZoneBooking $booking): array
    {
        $errors = [];

        if (! $zone->is_active) {
            $errors[] = 'La zona no está activa.';
        }

        if ($zone->ends_at !== null && $booking->date > $zone->ends_at->toDate()) {
            $errors[] = 'La reserva supera la fecha de vigencia de la zona.';
        }

        if ($zone->starts_at !== null && $booking->date < $zone->starts_at->toDate()) {
            $errors[] = 'La reserva está antes del inicio de vigencia de la zona.';
        }

        if (! $this->withinSchedule($zone, $booking)) {
            $errors[] = 'La reserva está fuera del horario habilitado de la zona.';
        }

        if ($this->conflicts($booking)) {
            $errors[] = 'Ya existe una reserva que se superpone en el horario solicitado.';
        }

        if ($this->capacityExceeded($zone, $booking)) {
            $errors[] = "La capacidad queda superada. Capacidad de la zona: {$zone->capacity} personas.";
        }

        return $errors;
    }

    public function toPending(CommonZone $zone, CommonZoneBooking $booking): CommonZoneBooking
    {
        return $this->finalizeStatus($zone, $booking);
    }

    private function finalizeStatus(CommonZone $zone, CommonZoneBooking $booking): CommonZoneBooking
    {
        $booking->status = $zone->requires_approval ? 'pending' : 'confirmed';
        $booking->qr_code = $booking->qr_code ?: Str::random(40);

        return $booking;
    }
}
