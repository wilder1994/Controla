<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\CommonZone;
use App\Models\CommonZoneBooking;
use App\Models\Resident;
use App\Services\Access\AuditLogger;
use App\Services\Access\ZoneBookingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ZoneBookingController extends Controller
{
    public function __construct(private readonly ZoneBookingService $service) {}

    public function index(): View
    {
        $user = auth()->user();

        $zones = CommonZone::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', today());
            })
            ->get();

        $myBookings = CommonZoneBooking::with('zone')
            ->where('user_id', $user->id)
            ->latest('date')
            ->take(20)
            ->get();

        return view('modules.client.zones.index', compact('zones', 'myBookings'));
    }

    public function create(Request $request): View
    {
        $user = auth()->user();

        $zones = CommonZone::where('is_active', true)->get();

        $selectedZone = $request->filled('zone_id')
            ? CommonZone::find($request->zone_id)
            : null;

        $units = collect();

        $resident = Resident::where('user_id', $user->id)->first();

        if ($resident !== null) {
            $units = $resident->housingUnits()->with('building')->get();
        }

        return view('modules.client.zones.book', compact('zones', 'selectedZone', 'units'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'common_zone_id' => 'required|exists:common_zones,id',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'people_count' => 'required|integer|min:1|max:100',
            'housing_unit_id' => 'nullable|exists:housing_units,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $zone = CommonZone::find($validated['common_zone_id']);

        if ($zone === null || ! $zone->is_active) {
            return back()->withErrors(['common_zone_id' => 'La zona seleccionada no está activa.'])->withInput();
        }

        $booking = CommonZoneBooking::make([
            'common_zone_id' => $zone->id,
            'user_id' => auth()->id(),
            'housing_unit_id' => $validated['housing_unit_id'] ?? null,
            'date' => $validated['date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'people_count' => $validated['people_count'],
            'notes' => $validated['notes'],
        ]);

        $errors = $this->service->validate($zone, $booking);

        if (! empty($errors)) {
            return back()->withErrors(['booking' => implode(' ', $errors)])->withInput();
        }

        $this->service->toPending($zone, $booking);

        $booking->save();

        app(AuditLogger::class)->record($booking, 'zone.booking.create', null, [
            'zone_id' => $zone->id,
            'date' => $booking->date->toDateString(),
            'start_time' => $booking->start_time->format('H:i'),
            'end_time' => $booking->end_time->format('H:i'),
            'status' => $booking->status,
        ]);

        $status = $zone->requires_approval
            ? 'Pendiente de aprobación.'
            : 'Confirmada. Preséntala al guardia con tu código QR.';

        return redirect()->route('client.zones.index')
            ->with('success', 'Reserva registrada: '.$status);
    }

    public function cancel(CommonZoneBooking $booking)
    {
        if ((int) $booking->user_id !== (int) auth()->id()) {
            abort(403);
        }

        if (in_array($booking->status, ['completed', 'cancelled', 'checked_in'], true)) {
            return back()->with('error', 'La reserva ya no puede cancelarse.');
        }

        $booking->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        app(AuditLogger::class)->record($booking, 'zone.booking.cancel', null, [
            'status' => 'cancelled',
        ]);

        return back()->with('success', 'Reserva cancelada.');
    }
}
