<?php
namespace App\Http\Controllers\Access;

use App\Http\Controllers\Controller;
use App\Models\AccessLog;
use App\Models\Visitor;
use App\Models\Resident;
use App\Models\Vehicle;
use App\Models\Location;
use App\Models\HousingUnit;
use App\Models\User;
use App\Services\Access\AuditLogger;
use App\Services\Access\BlocklistGuard;
use Illuminate\Http\Request;

class AccessLogController extends Controller
{
    public function index()
    {
        $activeLogs = AccessLog::with(['visitor', 'resident', 'housingUnit.building', 'host', 'location', 'vehicle'])
            ->where('status', 'active')
            ->latest('entry_time')
            ->get()
            ->map(function ($log) {
                $log->hours_inside = $log->entry_time->diffInHours(now());
                $log->alert_long_stay = $log->hours_inside >= config('access.alerts.long_stay_hours');
                return $log;
            });

        $todayLogs = AccessLog::with(['visitor', 'resident', 'housingUnit.building', 'host', 'location'])
            ->whereDate('entry_time', today())
            ->latest('entry_time')
            ->paginate(20);

        return view('modules.access.logs.index', compact('activeLogs', 'todayLogs'));
    }

    public function entry()
    {
        $locations = Location::where('is_active', true)->get();
        $hosts = User::role('anfitrion')->get();
        $buildings = \App\Models\Building::where('is_active', true)->get();
        $housingUnits = HousingUnit::where('is_active', true)->with('building')->get();
        return view('modules.access.logs.entry', compact('locations', 'hosts', 'buildings', 'housingUnits'));
    }

    public function exitPage()
    {
        return view('modules.access.logs.exit');
    }

    public function storeEntry(Request $request)
    {
        $validated = $request->validate([
            'visitor_id' => 'required|exists:visitors,id',
            'housing_unit_id' => 'nullable|exists:housing_units,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'host_id' => 'required|exists:users,id',
            'location_id' => 'required|exists:locations,id',
            'access_type' => 'required|in:visitor,visitor_vehicle',
            'purpose' => 'nullable|string|max:255',
            'company_visited' => 'nullable|string|max:150',
            'screening_temp' => 'nullable|numeric|min:34|max:42',
            'notes' => 'nullable|string',
        ]);

        $visitorBlock = app(BlocklistGuard::class)->checkPerson(
            visitor: Visitor::find($validated['visitor_id'])
        );

        if ($visitorBlock !== null) {
            return back()->withErrors(['visitor_id' => '🚫 Ingreso bloqueado por lista de bloqueo: '.$visitorBlock->reason])->withInput();
        }

        if (! empty($validated['vehicle_id'])) {
            $vehicleBlock = app(BlocklistGuard::class)->checkVehicle(
                vehicle: Vehicle::find($validated['vehicle_id'])
            );

            if ($vehicleBlock !== null) {
                return back()->withErrors(['vehicle_id' => '🚫 Vehículo bloqueado: '.$vehicleBlock->reason])->withInput();
            }
        }

        $validated['authorized_by'] = auth()->id();
        $validated['entry_time'] = now();
        $validated['status'] = 'active';

        $log = AccessLog::create($validated);

        app(AuditLogger::class)->record($log, 'access.entry', null, [
            'access_type' => $log->access_type,
            'visitor_id' => $log->visitor_id,
            'location_id' => $log->location_id,
            'entry_time' => $log->entry_time->toDateTimeString(),
        ]);

        return redirect()->route('access.logs.index')
            ->with('success', 'Ingreso registrado exitosamente.');
    }

    public function markExit(Request $request, AccessLog $accessLog)
    {
        if ($accessLog->status !== 'active') {
            return back()->with('error', 'Este registro ya tiene una salida registrada.');
        }

        $data = [
            'exit_time' => now(),
            'status' => 'completed',
        ];

        if ($request->boolean('has_custody')) {
            $custodyData = $request->validate([
                'custody_description' => 'required|string|max:1000',
                'custody_receiver_name' => 'nullable|string|max:255',
            ]);
            $data['has_custody'] = true;
            $data['custody_description'] = $custodyData['custody_description'];
            $data['custody_receiver_name'] = $custodyData['custody_receiver_name'];
            $data['custody_received_at'] = now();
        }

        $accessLog->update($data);

        app(AuditLogger::class)->record($accessLog, 'access.exit', [
            'status' => $accessLog->getOriginal('status'),
        ], [
            'exit_time' => $accessLog->exit_time?->toDateTimeString(),
            'status' => $accessLog->status,
            'has_custody' => $accessLog->has_custody,
        ]);

        return redirect()->route('access.logs.index')
            ->with('success', 'Salida registrada exitosamente.');
    }

    public function scanExit(Request $request)
    {
        $request->validate([
            'document_number' => 'required_if:log_id,null|string|max:50',
            'log_id' => 'nullable|exists:access_logs,id',
            'has_custody' => 'boolean',
            'custody_description' => 'required_if:has_custody,1|string|max:1000',
            'custody_receiver_name' => 'nullable|string|max:255',
        ]);

        if (! empty($request->log_id)) {
            $log = AccessLog::with(['visitor', 'resident', 'vehicle', 'location'])
                ->where('status', 'active')
                ->find($request->log_id);

            if ($log === null) {
                return response()->json(['error' => 'El registro ya tiene salida o no existe.'], 422);
            }

            $data = [
                'exit_time' => now(),
                'status' => 'completed',
            ];

            if ($request->boolean('has_custody')) {
                $data['has_custody'] = true;
                $data['custody_description'] = $request->custody_description;
                $data['custody_receiver_name'] = $request->custody_receiver_name;
                $data['custody_received_at'] = now();
            }

            $log->update($data);

            app(AuditLogger::class)->record($log, 'access.exit', ['status' => 'active'], [
                'exit_time' => $log->exit_time?->toDateTimeString(),
                'status' => 'completed',
                'via' => 'kiosco',
            ]);

            return response()->json([
                'ok' => true,
                'message' => 'Salida registrada exitosamente.',
                'id' => $log->id,
            ]);
        }

        $logs = $this->activeLogsForDocument($request->document_number);

        if ($logs->isEmpty()) {
            return response()->json([
                'found' => false,
                'message' => 'No hay ingresos activos para el documento ingresado.',
            ]);
        }

        return response()->json([
            'found' => true,
            'matches' => $logs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'name' => $log->visitor?->full_name ?? $log->resident?->full_name ?? $log->user?->name ?? '-',
                    'entry_time' => $log->entry_time->format('d/m/Y H:i'),
                    'duration_hours' => (int) $log->entry_time->diffInHours(now()),
                    'destination' => $log->housingUnit?->full_label ?? $log->location?->name,
                    'has_vehicle' => $log->vehicle?->plate ?? ($log->access_type === 'resident_vehicle' ? 'Sí' : null),
                ];
            }),
        ]);
    }

    private function activeLogsForDocument(string $documentNumber)
    {
        $visitorIds = Visitor::whereNull('deleted_at')
            ->where('document_number', $documentNumber)
            ->pluck('id');

        $residentIds = Resident::where('document_number', $documentNumber)->pluck('id');

        $vehicleIds = Vehicle::whereRaw('upper(plate) = ?', [strtoupper($documentNumber)])->pluck('id');

        return AccessLog::with(['visitor', 'resident', 'vehicle', 'location', 'housingUnit'])
            ->where('status', 'active')
            ->where(function ($q) use ($visitorIds, $residentIds, $vehicleIds) {
                $q->whereIn('visitor_id', $visitorIds)
                    ->orWhereIn('resident_id', $residentIds)
                    ->orWhereIn('vehicle_id', $vehicleIds);
            })
            ->latest('entry_time')
            ->get();
    }

    public function bulkExit(Request $request)
    {
        $count = AccessLog::where('status', 'active')
            ->whereDate('entry_time', '<=', today())
            ->update([
                'exit_time' => now(),
                'status' => 'completed',
            ]);

        return redirect()->route('access.logs.index')
            ->with('success', "Salida masiva: {$count} registro(s) actualizado(s).");
    }
}
