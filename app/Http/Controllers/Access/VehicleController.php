<?php

namespace App\Http\Controllers\Access;

use App\Http\Controllers\Controller;
use App\Models\Resident;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Visitor;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index()
    {
        $vehicles = Vehicle::with(['visitor', 'owner', 'resident'])->latest()->paginate(15);

        return view('modules.access.vehicles.index', compact('vehicles'));
    }

    public function create()
    {
        $visitors = Visitor::all();
        $users = User::all();
        $residents = Resident::where('is_active', true)->get();

        return view('modules.access.vehicles.create', compact('visitors', 'users', 'residents'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'visitor_id' => 'nullable|exists:visitors,id',
            'user_id' => 'nullable|exists:users,id',
            'resident_id' => 'nullable|exists:residents,id',
            'plate' => 'required|string|max:20|unique:vehicles,plate',
            'brand' => 'nullable|string|max:50',
            'model' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:30',
            'type' => 'required|in:carro,moto,camion',
        ]);

        Vehicle::create($validated);

        return redirect()->route('access.vehicles.index')
            ->with('success', 'Vehículo registrado exitosamente.');
    }

    public function edit(Vehicle $vehicle)
    {
        $visitors = Visitor::all();
        $users = User::all();
        $residents = Resident::where('is_active', true)->get();

        return view('modules.access.vehicles.edit', compact('vehicle', 'visitors', 'users', 'residents'));
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $validated = $request->validate([
            'visitor_id' => 'nullable|exists:visitors,id',
            'user_id' => 'nullable|exists:users,id',
            'resident_id' => 'nullable|exists:residents,id',
            'plate' => 'required|string|max:20|unique:vehicles,plate,'.$vehicle->id,
            'brand' => 'nullable|string|max:50',
            'model' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:30',
            'type' => 'required|in:carro,moto,camion',
        ]);

        $vehicle->update($validated);

        return redirect()->route('access.vehicles.index')
            ->with('success', 'Vehículo actualizado exitosamente.');
    }

    public function destroy(Vehicle $vehicle)
    {
        $vehicle->delete();

        return redirect()->route('access.vehicles.index')
            ->with('success', 'Vehículo eliminado exitosamente.');
    }

    public function searchResidentVehicleJson(Request $request)
    {
        $query = $request->get('q');
        $vehicles = Vehicle::with('resident')
            ->whereNotNull('resident_id')
            ->where('plate', 'like', "%{$query}%")
            ->take(10)
            ->get(['id', 'plate', 'brand', 'model', 'color', 'type', 'resident_id']);

        return response()->json($vehicles);
    }

    public function searchJson(Request $request)
    {
        $query = $request->get('q');
        $vehicles = Vehicle::with('visitor')
            ->where('plate', 'like', "%{$query}%")
            ->take(10)
            ->get(['id', 'plate', 'brand', 'model', 'color', 'type', 'visitor_id']);

        return response()->json($vehicles);
    }
}
