<?php

namespace App\Http\Controllers\Access;

use App\Enums\InstallationKind;
use App\Enums\ObservatoryReportKind;
use App\Enums\ObservatoryReporterRole;
use App\Enums\ObservatoryReportSource;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\GuardLog;
use App\Models\Location;
use App\Models\SupervisionCode;
use App\Models\User;
use App\Services\Observatory\SubmitObservatoryReportService;
use App\Notifications\AlertaOperativa;
use App\Services\Access\AuditLogger;
use App\Services\Access\GeoService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
        $locations = Location::where('is_active', true)->with('installation')->get();
        $observatoryLocationIds = $locations
            ->filter(fn (Location $location) => $this->observatoryEligible($location))
            ->pluck('id')
            ->values()
            ->all();

        return view('modules.access.guard_logs.create', [
            'locations' => $locations,
            'observatoryLocationIds' => $observatoryLocationIds,
            'observatoryKinds' => ObservatoryReportKind::options(),
        ]);
    }

    public function store(Request $request)
    {
        $geoRequired = config('access.geo.required', true);

        $rules = [
            'location_id' => 'required|exists:locations,id',
            'log_time' => 'required|date',
            'type' => 'required|in:novedad,turno,incidente,general,revista',
            'shift_type' => 'required|in:diurno,nocturno',
            'description' => 'required|string',
            'latitude' => [$geoRequired ? 'required' : 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => [$geoRequired ? 'required' : 'nullable', 'numeric', 'between:-180,180'],
            'signed' => 'accepted',
            'supervision_code' => [
                $request->input('requires_supervisor') || in_array($request->input('type'), ['incidente', 'novedad', 'revista'], true) ? 'required' : 'nullable',
                'string',
                'max:50',
            ],
            'to_observatory' => ['sometimes', 'boolean'],
            'observatory_kind' => [
                $request->boolean('to_observatory') && $request->input('type') === 'novedad' ? 'required' : 'nullable',
                'string',
                Rule::enum(ObservatoryReportKind::class),
            ],
            'observatory_anonymous' => ['sometimes', 'boolean'],
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

        $toObservatory = $request->boolean('to_observatory') && $validated['type'] === 'novedad';
        $observatoryKind = $validated['observatory_kind'] ?? null;
        $observatoryAnonymous = $request->boolean('observatory_anonymous');
        unset($validated['to_observatory'], $validated['observatory_kind'], $validated['observatory_anonymous']);

        $validated['user_id'] = auth()->id();
        $validated['signed_at'] = $request->boolean('signed') ? now() : null;

        $code = null;
        $supervisorName = null;
        if (! empty($validated['supervision_code'])) {
            $resolved = $this->resolveSupervisorSignature((string) $validated['supervision_code']);

            if ($resolved === null) {
                return back()->withErrors(['supervision_code' => 'El código de supervisor no es válido o está inactivo.'])->withInput();
            }

            $code = $resolved['code'];
            $supervisorName = $resolved['name'];
        }

        $log = GuardLog::create(array_merge($validated, [
            'supervision_code_id' => $code?->id,
            'supervisor_name' => $supervisorName,
        ]));

        app(AuditLogger::class)->record($log, 'guard_log.create', null, [
            'type' => $log->type,
            'location_id' => $log->location_id,
            'log_time' => $log->log_time->toDateTimeString(),
            'supervisor_name' => $log->supervisor_name,
        ]);

        $message = 'Minuta registrada exitosamente.';
        if ($toObservatory && $location !== null) {
            $location->loadMissing('installation.client');
            if ($this->observatoryEligible($location) && $location->installation?->client) {
                try {
                    $user = $request->user();
                    app(SubmitObservatoryReportService::class)->execute($location->installation->client, [
                        'installation_id' => (int) $location->installation_id,
                        'kind' => (string) $observatoryKind,
                        'body' => (string) $validated['description'],
                        'is_anonymous' => $observatoryAnonymous,
                        'source' => ObservatoryReportSource::Porteria,
                        'reporter_role' => ObservatoryReporterRole::Vigilante,
                        'reporter_name' => $observatoryAnonymous ? null : $user?->name,
                        'reported_by' => $user,
                        'latitude' => $validated['latitude'] ?? null,
                        'longitude' => $validated['longitude'] ?? null,
                    ], $request->ip());
                    $message = 'Minuta registrada y enviada al Observatorio.';
                } catch (ValidationException) {
                    $message = 'Minuta registrada. No se pudo enviar al Observatorio.';
                }
            }
        }

        return redirect()->route('access.guard_logs.index')
            ->with('success', $message);
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
            'description' => '🚨 PANIC: '.$request->description,
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

    /** @return array{code: ?SupervisionCode, name: string}|null */
    private function resolveSupervisorSignature(string $raw): ?array
    {
        $code = SupervisionCode::query()
            ->where('code', $raw)
            ->where('is_active', true)
            ->first();

        if ($code !== null) {
            return ['code' => $code, 'name' => $code->name];
        }

        $supervisor = User::query()
            ->where('supervisor_code', $raw)
            ->where('is_active', true)
            ->role('supervisor')
            ->when($this->porteriaCompanyId(), function ($query, int $companyId): void {
                $query->where('security_company_id', $companyId);
            })
            ->first();

        if ($supervisor === null) {
            return null;
        }

        return ['code' => null, 'name' => $supervisor->name];
    }

    private function observatoryEligible(?Location $location): bool
    {
        $installation = $location?->installation;
        if ($installation === null || ! $installation->is_active) {
            return false;
        }
        if ((string) $installation->kind !== InstallationKind::Colegio->value) {
            return false;
        }

        return Location::query()
            ->withoutGlobalScopes()
            ->where('installation_id', $installation->id)
            ->where('is_active', true)
            ->exists();
    }

    private function porteriaCompanyId(): ?int
    {
        $clientId = app(TenantContext::class)->clientId();

        if ($clientId === null) {
            $companyId = auth()->user()?->security_company_id;

            return $companyId !== null ? (int) $companyId : null;
        }

        $companyId = Client::query()->whereKey($clientId)->value('security_company_id');

        return $companyId !== null ? (int) $companyId : null;
    }
}
