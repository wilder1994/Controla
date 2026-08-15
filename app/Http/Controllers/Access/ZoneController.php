<?php
namespace App\Http\Controllers\Access;

use App\Http\Controllers\Controller;
use App\Models\CommonZone;
use App\Models\CommonZoneBooking;
use App\Services\Access\AuditLogger;
use Illuminate\Http\Request;

class ZoneController extends Controller
{
    public function index()
    {
        $zones = CommonZone::withCount(['bookings' => fn ($q) => $q->where('date', today())])
            ->latest()
            ->paginate(15);

        $todayBookings = CommonZoneBooking::with(['zone', 'user'])
            ->where('date', today())
            ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
            ->latest()
            ->get();

        return view('modules.access.zones.index', compact('zones', 'todayBookings'));
    }

    public function create()
    {
        return view('modules.access.zones.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'type' => 'required|in:salon,piscina,gimnasio,parque,cancha,biblioteca,otro',
            'capacity' => 'required|integer|min:1|max:500',
            'requires_approval' => 'boolean',
            'open_time' => 'required|date_format:H:i',
            'close_time' => 'required|date_format:H:i|after:open_time',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'is_active' => 'boolean',
        ]);

        $zone = CommonZone::create($validated);

        app(AuditLogger::class)->record($zone, 'zone.create', null, [
            'name' => $zone->name,
            'type' => $zone->type,
            'capacity' => $zone->capacity,
        ]);

        return redirect()->route('access.zones.index')
            ->with('success', 'Zona común creada exitosamente.');
    }

    public function destroy(CommonZone $zone)
    {
        $zone->update(['is_active' => false]);

        app(AuditLogger::class)->record($zone, 'zone.deactivate', null, [
            'name' => $zone->name,
        ]);

        return redirect()->route('access.zones.index')
            ->with('success', 'Zona desactivada.');
    }

    public function checkin(Request $request)
    {
        $request->validate([
            'qr_code' => 'required|string|max:100',
        ]);

        $booking = CommonZoneBooking::with(['zone', 'user'])
            ->where('qr_code', $request->qr_code)
            ->whereIn('status', ['pending', 'confirmed'])
            ->first();

        if ($booking === null) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Reserva no encontrada, ya usada, cancelada o no vigente.'], 422);
            }

            return back()->withErrors(['qr_code' => 'Reserva no válida para dar uso.'])->withInput();
        }

        $booking->update([
            'status' => 'checked_in',
            'checked_in_at' => now(),
        ]);

        app(AuditLogger::class)->record($booking, 'zone.checkin', null, [
            'zone' => $booking->zone?->name,
            'user_id' => $booking->user_id,
            'checked_in_at' => now()->toDateTimeString(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Uso de zona confirmado.',
                'booking' => [
                    'id' => $booking->id,
                    'zone' => $booking->zone?->name,
                    'user' => $booking->user?->name,
                    'time' => $booking->start_time->format('H:i').' - '.$booking->end_time->format('H:i'),
                ],
            ]);
        }

        return redirect()->route('access.zones.index')
            ->with('success', 'Uso de zona confirmado para '.$booking->zone?->name.'.');
    }

    public function complete(CommonZoneBooking $booking)
    {
        if (! in_array($booking->status, ['checked_in', 'confirmed'], true)) {
            return back()->with('error', 'La reserva no está en un estado que permita completarla.');
        }

        $booking->update(['status' => 'completed']);

        return back()->with('success', 'Reserva marcada como completada.');
    }

    public function cancel(CommonZoneBooking $booking)
    {
        if (in_array($booking->status, ['completed', 'cancelled'], true)) {
            return back()->with('error', 'La reserva ya fue completada o cancelada.');
        }

        $booking->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return back()->with('success', 'Reserva cancelada.');
    }
}