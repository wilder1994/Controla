<?php

namespace App\Http\Controllers\Access;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Supervision;
use App\Models\SupervisionCode;
use App\Models\User;
use App\Services\Access\AuditLogger;
use App\Services\Access\GeoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SupervisionController extends Controller
{
    public function index()
    {
        $supervisions = Supervision::with(['user', 'location', 'supervisionCode', 'attachments'])
            ->latest('log_time')
            ->paginate(15);

        return view('modules.access.supervision.index', compact('supervisions'));
    }

    public function unlockForm()
    {
        return view('modules.access.supervision.unlock');
    }

    public function unlock(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $code = SupervisionCode::query()
            ->where('code', $request->code)
            ->where('is_active', true)
            ->first();

        if ($code !== null) {
            session([
                'supervision.authorized' => true,
                'supervision.supervisor_code_id' => $code->id,
                'supervision.supervisor_name' => $code->name,
            ]);

            return redirect()->route('access.supervision.index')
                ->with('success', 'Código de supervisión verificado. Bienvenido, '.$code->name.'.');
        }

        $supervisor = User::query()
            ->where('supervisor_code', $request->code)
            ->where('is_active', true)
            ->role('supervisor')
            ->first();

        if ($supervisor === null) {
            return back()
                ->withErrors(['code' => 'El código ingresado no es válido o está desactivado.'])
                ->withInput();
        }

        session([
            'supervision.authorized' => true,
            'supervision.supervisor_code_id' => null,
            'supervision.supervisor_name' => $supervisor->name,
        ]);

        return redirect()->route('access.supervision.index')
            ->with('success', 'Código de revista verificado. Bienvenido, '.$supervisor->name.'.');
    }

    public function exit()
    {
        session()->forget(['supervision.authorized', 'supervision.supervisor_code_id', 'supervision.supervisor_name']);

        return redirect()->route('access.supervision.index')
            ->with('success', 'Sesión de supervisión cerrada.');
    }

    public function create()
    {
        $locations = Location::where('is_active', true)->get();

        return view('modules.access.supervision.create', compact('locations'));
    }

    public function store(Request $request)
    {
        $geoRequired = config('access.geo.required', true);

        $validated = $request->validate([
            'location_id' => 'required|exists:locations,id',
            'log_date' => 'required|date',
            'type' => 'required|in:general,inspeccion,novedad,incidente,rutina',
            'shift_type' => 'required|in:diurno,nocturno',
            'supervisor_name' => 'nullable|string|max:255',
            'description' => 'required|string',
            'latitude' => [$geoRequired ? 'required' : 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => [$geoRequired ? 'required' : 'nullable', 'numeric', 'between:-180,180'],
            'signed' => 'accepted',
            'photos.*' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:10240',
            'documents.*' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,webp|max:20480',
        ]);

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

        $supervision = Supervision::create([
            'user_id' => auth()->id(),
            'location_id' => $validated['location_id'],
            'log_time' => $validated['log_date'],
            'type' => $validated['type'],
            'shift_type' => $validated['shift_type'],
            'description' => $validated['description'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'signed_at' => now(),
            'supervision_code_id' => session('supervision.supervisor_code_id'),
            'supervisor_name' => $validated['supervisor_name'] ?: session('supervision.supervisor_name'),
        ]);

        $this->storeAttachments($supervision, $request, 'photos', 'photo');
        $this->storeAttachments($supervision, $request, 'documents', 'document');

        app(AuditLogger::class)->record($supervision, 'supervision.create', null, [
            'type' => $supervision->type,
            'location_id' => $supervision->location_id,
            'log_time' => $supervision->log_time?->toDateTimeString(),
        ]);

        return redirect()->route('access.supervision.show', $supervision)
            ->with('success', 'Registro de supervisión creado exitosamente.');
    }

    public function show(Supervision $supervision)
    {
        $supervision->load(['user', 'location', 'supervisionCode', 'attachments']);

        return view('modules.access.supervision.show', compact('supervision'));
    }

    public function destroy(Supervision $supervision)
    {
        Storage::disk('public')->delete($supervision->attachments->pluck('file_path')->all());
        $supervision->attachments()->delete();
        $supervision->delete();

        return redirect()->route('access.supervision.index')
            ->with('success', 'Registro de supervisión eliminado.');
    }

    private function storeAttachments(Supervision $supervision, Request $request, string $input, string $kind): void
    {
        foreach ($request->file($input, []) as $file) {
            $path = $file->store('supervision/'.$kind.'s', 'public');

            $supervision->attachments()->create([
                'kind' => $kind,
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'uploaded_by' => auth()->id(),
            ]);
        }
    }
}
