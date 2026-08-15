<?php
namespace App\Http\Controllers\Access;

use App\Http\Controllers\Controller;
use App\Models\GuardLog;
use App\Models\Location;
use App\Models\SupervisionCode;
use App\Models\User;
use App\Notifications\AlertaOperativa;
use App\Services\Access\AuditLogger;
use App\Services\Access\GeoService;
use Illuminate\Http\Request;

class GuardLogController extends Controller
{
    public function index()
    {
        $logs = GuardLog::with(['user', 'location'])
            ->latest('log_time')
            ->paginate(15);

        return view('modules.access.guard_logs.index', compact('logs'));
    }

    public function create()
    {
        $locations = Location::where('is_active', true)->get();
        return view('modules.access.guard_logs.create', compact('locations'));
    }

    public function store(Request $request)
    {
        $geoRequired = config('access.geo.required', true);

        $rules = [
            'location_id' => 'required|exists:locations,id',
            'log_time' => 'required|date',
            'type' => 'required|in:novedad,turno,incidente,general',
            'shift_type' => 'required|in:diurno,nocturno',
            'description' => 'required|string',
            'latitude' => [$geoRequired ? 'required' : 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => [$geoRequired ? 'required' : 'nullable', 'numeric', 'between:-180,180'],
            'signed' => 'accepted',
            'supervision_code' => [
                $request->input('requires_supervisor') || in_array($request->input('type'), ['incidente', 'novedad'], true) ? 'required' : 'nullable',
                'string',
                'max:50',
            ],
        ];

        $validated = $request->validate($rules);

        $location = Location::find($validated['location_id']);

        if ($location !== null && $geoRequired) {
            $geoErrors = app(GeoService::class)->validateAgainstLocation(
                $location,
                $validated['latitude'] ?? null,
                $validated['longitude'] ?? null
            );

            if (! empty($geoErrors)) {
                return back()->withErrors(['geo' => implode(' ', $geoErrors)])->withInput();
            }
        }

        $validated['user_id'] = auth()->id();
        $validated['signed_at'] = $request->boolean('signed') ? now() : null;

        $code = null;
        if (! empty($validated['supervision_code'])) {
            $code = SupervisionCode::where('code', $validated['supervision_code'])
                ->where('is_active', true)
                ->first();

            if ($code === null) {
                return back()->withErrors(['supervision_code' => 'El código de supervisor no es válido o está inactivo.'])->withInput();
            }
        }

        $log = GuardLog::create(array_merge($validated, [
            'supervision_code_id' => $code?->id,
            'supervisor_name' => $code?->name,
        ]));

        app(AuditLogger::class)->record($log, 'guard_log.create', null, [
            'type' => $log->type,
            'location_id' => $log->location_id,
            'log_time' => $log->log_time->toDateTimeString(),
            'supervisor_name' => $log->supervisor_name,
        ]);

        return redirect()->route('access.guard_logs.index')
            ->with('success', 'Minuta registrada exitosamente.');
    }

    public function panic(Request $request)
    {
        $request->validate([
            'location_id' => 'required|exists:locations,id',
            'description' => 'required|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $log = GuardLog::create([
            'client_id' => auth()->user()->primary_client_id,
            'user_id' => auth()->id(),
            'location_id' => $request->location_id,
            'log_time' => now(),
            'type' => 'incidente',
            'shift_type' => now()->hour >= 6 && now()->hour < 18 ? 'diurno' : 'nocturno',
            'description' => '🚨 PANIC: ' . $request->description,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'is_panic' => true,
            'signed_at' => now(),
        ]);

        $locationName = $log->location?->name ?? 'Portería';

        $managers = User::role(['client-admin', 'admin-accesos', 'company-admin'])
            ->where('primary_client_id', auth()->user()->primary_client_id)
            ->whereKeyNot(auth()->id())
            ->get();

        $managers->each(fn (User $user) => $user->notify(new AlertaOperativa(
            title: '🚨 Alerta de pánico',
            message: auth()->user()->name." generó una alerta de pánico en {$locationName}.",
            level: 'panic',
            url: route('access.guard_logs.show', $log),
        )));

        app(AuditLogger::class)->record($log, 'panic', null, [
            'location_id' => $log->location_id,
            'description' => $log->description,
        ]);

        return redirect()->route('access.guard_logs.index')
            ->with('success', '🚨 Alerta de pánico registrada. Personal notificado.');
    }

    public function show(GuardLog $guardLog)
    {
        $guardLog->load(['user', 'location']);
        return view('modules.access.guard_logs.show', compact('guardLog'));
    }

    public function destroy(GuardLog $guardLog)
    {
        $guardLog->delete();
        return redirect()->route('access.guard_logs.index')
            ->with('success', 'Minuta eliminada.');
    }
}
