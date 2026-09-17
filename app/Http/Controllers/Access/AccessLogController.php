<?php

declare(strict_types=1);

namespace App\Http\Controllers\Access;

use App\Http\Controllers\Controller;
use App\Models\AccessLog;
use App\Services\Access\LookupPorteriaSubjectService;
use App\Services\Access\PorteriaDoorService;
use App\Services\Access\RegisterPorteriaMovementService;
use App\Support\Access\DataUrlToUploadedFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccessLogController extends Controller
{
    public function index(): View
    {
        $activeLogs = AccessLog::query()
            ->with(['visitor', 'structureMember.structure', 'resident', 'location', 'vehicle'])
            ->where('status', 'active')
            ->latest('entry_time')
            ->get()
            ->map(function (AccessLog $log) {
                $log->hours_inside = $log->entry_time->diffInHours(now());
                $log->alert_long_stay = $log->hours_inside >= config('access.alerts.long_stay_hours');

                return $log;
            });

        $todayLogs = AccessLog::query()
            ->with(['visitor', 'structureMember', 'location', 'vehicle'])
            ->whereDate('entry_time', today())
            ->latest('entry_time')
            ->paginate(20);

        return view('modules.access.logs.index', compact('activeLogs', 'todayLogs'));
    }

    public function lookup(Request $request, LookupPorteriaSubjectService $lookup): JsonResponse
    {
        return response()->json($lookup->search((string) $request->query('q', '')));
    }

    public function entry(LookupPorteriaSubjectService $lookup, PorteriaDoorService $doors): View
    {
        return view('modules.access.logs.entry', [
            'nodes' => $lookup->nodes(),
            'memberTypes' => $lookup->memberTypes(),
            'door' => $doors->current(request()),
            'withVehicle' => request()->boolean('with_vehicle'),
        ]);
    }

    public function storeEntry(Request $request, RegisterPorteriaMovementService $register): RedirectResponse
    {
        $validated = $request->validate([
            'subject_kind' => 'required|in:member,visitor',
            'with_vehicle' => 'nullable|boolean',
            'member_id' => 'nullable|integer',
            'visitor_id' => 'nullable|integer',
            'vehicle_id' => 'nullable|integer',
            'structure_id' => 'nullable|integer',
            'member_type_id' => 'nullable|integer',
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'document_type' => 'nullable|string|max:20',
            'document_number' => 'nullable|string|max:50',
            'birth_date' => 'nullable|date',
            'plate' => 'nullable|string|max:20',
            'vehicle_brand' => 'nullable|string|max:80',
            'vehicle_color' => 'nullable|string|max:40',
            'purpose' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'person_photo_data' => 'nullable|string',
            'vehicle_photo_data' => 'nullable|string',
        ]);

        $validated['with_vehicle'] = $request->boolean('with_vehicle');

        $register->enter(
            $request->user(),
            $validated,
            DataUrlToUploadedFile::make($validated['person_photo_data'] ?? null, 'persona'),
            DataUrlToUploadedFile::make($validated['vehicle_photo_data'] ?? null, 'vehiculo'),
        );

        return redirect()->route('access.logs.index')->with('success', 'Ingreso registrado.');
    }

    public function exitPage(): View
    {
        $activeLogs = AccessLog::query()
            ->with(['visitor', 'structureMember', 'vehicle', 'location'])
            ->where('status', 'active')
            ->latest('entry_time')
            ->get();

        return view('modules.access.logs.exit', compact('activeLogs'));
    }

    public function markExit(Request $request, AccessLog $accessLog): RedirectResponse
    {
        if ($accessLog->status !== 'active') {
            return back()->with('error', 'Este registro ya tiene salida.');
        }

        $accessLog->update([
            'exit_time' => now(),
            'status' => 'completed',
            'has_custody' => $request->boolean('has_custody'),
            'custody_description' => $request->input('custody_description'),
            'custody_receiver_name' => $request->input('custody_receiver_name'),
            'custody_received_at' => $request->boolean('has_custody') ? now() : null,
        ]);

        return back()->with('success', 'Salida registrada.');
    }

    public function scanExit(Request $request): JsonResponse
    {
        if ($request->filled('log_id')) {
            $log = AccessLog::query()->whereKey($request->integer('log_id'))->where('status', 'active')->first();
            if ($log === null) {
                return response()->json(['error' => 'Ingreso no encontrado.'], 404);
            }

            $log->update([
                'exit_time' => now(),
                'status' => 'completed',
                'has_custody' => $request->boolean('has_custody'),
                'custody_description' => $request->input('custody_description'),
                'custody_receiver_name' => $request->input('custody_receiver_name'),
                'custody_received_at' => $request->boolean('has_custody') ? now() : null,
            ]);

            return response()->json(['found' => true, 'message' => 'Salida registrada.']);
        }

        $q = trim((string) ($request->input('document_number') ?: $request->input('qr_code') ?: ''));
        if ($q === '') {
            return response()->json(['error' => 'Indica documento o placa.'], 422);
        }

        $logs = AccessLog::query()
            ->with(['visitor', 'structureMember.structure', 'vehicle', 'location'])
            ->where('status', 'active')
            ->where(function ($query) use ($q): void {
                $query->where('qr_code', $q)
                    ->orWhereHas('visitor', fn ($v) => $v->where('document_number', 'like', "%{$q}%"))
                    ->orWhereHas('structureMember', fn ($m) => $m->where('document_number', 'like', "%{$q}%"))
                    ->orWhereHas('vehicle', fn ($veh) => $veh->where('plate', 'like', "%{$q}%"));
            })
            ->latest('entry_time')
            ->get();

        if ($logs->isEmpty()) {
            return response()->json(['found' => false, 'message' => 'Sin ingresos activos.', 'matches' => []]);
        }

        return response()->json([
            'found' => true,
            'matches' => $logs->map(fn (AccessLog $log): array => [
                'id' => $log->id,
                'name' => $log->subjectName(),
                'destination' => $log->structureMember?->structure?->name ?? $log->location?->name ?? '—',
                'entry_time' => $log->entry_time->format('H:i'),
                'duration_hours' => $log->entry_time->diffInHours(now()),
                'has_vehicle' => $log->vehicle?->plate,
            ])->all(),
        ]);
    }

    public function bulkExit(): RedirectResponse
    {
        AccessLog::query()->where('status', 'active')->update([
            'exit_time' => now(),
            'status' => 'completed',
        ]);

        return back()->with('success', 'Salida masiva registrada.');
    }
}
