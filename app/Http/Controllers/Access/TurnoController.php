<?php

namespace App\Http\Controllers\Access;

use App\Http\Controllers\Controller;
use App\Models\GuardShift;
use App\Services\Access\PorteriaDoorService;
use App\Services\Access\TurnoService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TurnoController extends Controller
{
    public function __construct(
        private readonly TurnoService $turnoService,
        private readonly PorteriaDoorService $doors,
    ) {}

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

    public function open(Request $request)
    {
        $locations = $this->doors->activeDoors();
        $this->doors->bindSingleIfOnlyOne($request);
        $selectedId = $this->doors->current($request)?->id;

        return view('modules.access.turnos.open', compact('locations', 'selectedId'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $doorIds = $this->doors->activeDoors()->pluck('id')->all();

        $validated = $request->validate([
            'location_id' => ['required', 'integer', Rule::in($doorIds)],
            'start_notes' => 'nullable|string|max:500',
        ], [
            'location_id.required' => 'Selecciona la puerta que vas a operar.',
            'location_id.in' => 'La puerta no es válida para este cliente.',
        ]);

        $locationId = (int) $validated['location_id'];
        $this->doors->remember($request, $locationId);

        if ($this->turnoService->hasOpenShiftFor($user)) {
            $shift = $this->turnoService->currentFor($user);
            $shift?->update(['location_id' => $locationId]);

            return redirect()->route('access.turnos.index')
                ->with('success', 'Puerta de operación actualizada.');
        }

        if ($this->turnoService->isShiftOptionalFor($user)) {
            return redirect()->route('access.dashboard')
                ->with('success', 'Puerta seleccionada. Ya puedes operar y el pánico usará esa puerta.');
        }

        $shift = $this->turnoService->open($user, $locationId, $validated['start_notes'] ?? null);
        $shift->load('location');

        return redirect()->route('access.turnos.index')
            ->with('success', 'Turno abierto a las '.$shift->started_at->format('H:i').' · '.$shift->location?->name);
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

        $request->session()->forget(PorteriaDoorService::SESSION_KEY);

        return redirect()->route('access.turnos.index')
            ->with('success', 'Turno cerrado. Reporte del turno guardado.');
    }
}
