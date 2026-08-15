<?php
namespace App\Http\Controllers\Access;

use App\Http\Controllers\Controller;
use App\Models\GuardShift;
use App\Models\Location;
use App\Services\Access\TurnoService;
use Illuminate\Http\Request;

class TurnoController extends Controller
{
    public function __construct(private readonly TurnoService $turnoService)
    {
    }

    public function index()
    {
        $user = auth()->user();

        $currentShift = $this->turnoService->currentFor($user);

        $history = GuardShift::with('location')
            ->where('user_id', $user->id)
            ->latest('started_at')
            ->paginate(15);

        return view('modules.access.turnos.index', compact('currentShift', 'history'));
    }

    public function open()
    {
        $locations = Location::where('is_active', true)->get();

        return view('modules.access.turnos.open', compact('locations'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        if ($this->turnoService->hasOpenShiftFor($user)) {
            return redirect()->route('access.turnos.index')
                ->with('info', 'Ya tienes un turno abierto.');
        }

        $validated = $request->validate([
            'location_id' => 'nullable|exists:locations,id',
            'start_notes' => 'nullable|string|max:500',
        ]);

        $shift = $this->turnoService->open($user, $validated['location_id'] ?: null, $validated['start_notes'] ?: null);

        return redirect()->route($shift->location_id ? 'access.turnos.index' : 'access.turnos.open')
            ->with('success', 'Turno abierto correctamente a las '.$shift->started_at->format('H:i').'.');
    }

    public function close(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'end_notes' => 'nullable|string|max:500',
        ]);

        $shift = $this->turnoService->close($user, $request->end_notes ?: null);

        if ($shift === null) {
            return back()->with('error', 'No tienes un turno abierto.');
        }

        return redirect()->route('access.turnos.index')
            ->with('success', 'Turno cerrado. Reporte del turno guardado.');
    }
}