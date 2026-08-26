<?php

namespace App\Http\Controllers\Access;

use App\Http\Controllers\Controller;
use App\Models\AccessLog;
use App\Models\Location;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Access\BlocklistGuard;
use Illuminate\Http\Request;

class VehicleAccessController extends Controller
{
    public function index()
    {
        $activeVehicles = AccessLog::with(['vehicle', 'user', 'resident', 'location'])
            ->whereIn('access_type', ['resident', 'resident_vehicle'])
            ->where('status', 'active')
            ->latest('entry_time')
            ->get();

        $todayLogs = AccessLog::with(['vehicle', 'user', 'resident', 'location'])
            ->whereIn('access_type', ['resident', 'resident_vehicle'])
            ->whereDate('entry_time', today())
            ->latest('entry_time')
            ->paginate(20);

        return view('modules.access.vehicle_access.index', compact('activeVehicles', 'todayLogs'));
    }

    public function entry()
    {
        $locations = Location::where('is_active', true)->get();
        $owners = User::all();

        return view('modules.access.vehicle_access.entry', compact('locations', 'owners'));
    }

    public function storeEntry(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'user_id' => 'nullable|exists:users,id',
            'resident_id' => 'nullable|exists:residents,id',
            'location_id' => 'required|exists:locations,id',
            'purpose' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $vehicleBlock = app(BlocklistGuard::class)->checkVehicle(
            vehicle: Vehicle::find($validated['vehicle_id'])
        );

        if ($vehicleBlock !== null) {
            return back()->withErrors(['vehicle_id' => '🚫 Vehículo en lista de bloqueo: '.$vehicleBlock->reason])->withInput();
        }

        $validated['authorized_by'] = auth()->id();
        $validated['entry_time'] = now();
        $validated['status'] = 'active';
        $validated['access_type'] = 'resident_vehicle';

        AccessLog::create($validated);

        return redirect()->route('access.vehicle_access.index')
            ->with('success', 'Ingreso vehicular registrado exitosamente.');
    }

    public function markExit(AccessLog $accessLog)
    {
        if ($accessLog->status !== 'active') {
            return back()->with('error', 'Este registro ya tiene salida.');
        }
        $accessLog->update(['exit_time' => now(), 'status' => 'completed']);

        return redirect()->route('access.vehicle_access.index')
            ->with('success', 'Salida vehicular registrada.');
    }

    public function searchVehicleJson(Request $request)
    {
        $query = $request->get('q');
        $vehicles = Vehicle::with('owner', 'resident')
            ->where('plate', 'like', "%{$query}%")
            ->where(function ($q) {
                $q->whereNotNull('user_id')->orWhereNotNull('resident_id');
            })
            ->take(10)
            ->get(['id', 'plate', 'brand', 'model', 'color', 'user_id', 'resident_id']);

        return response()->json($vehicles);
    }
}
