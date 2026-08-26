<?php

namespace App\Http\Controllers\Access;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Location;
use Illuminate\Http\Request;

class BuildingController extends Controller
{
    public function index()
    {
        $buildings = Building::with('location', 'housingUnits')->latest()->paginate(15);

        return view('modules.access.buildings.index', compact('buildings'));
    }

    public function create()
    {
        $locations = Location::where('is_active', true)->get();

        return view('modules.access.buildings.create', compact('locations'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:buildings,code',
            'name' => 'required|string|max:100',
            'address' => 'nullable|string|max:255',
            'type' => 'required|in:torre,bloque,casa_independiente',
            'location_id' => 'nullable|exists:locations,id',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        Building::create($validated);

        return redirect()->route('access.buildings.index')
            ->with('success', 'Torre/Bloque creado exitosamente.');
    }

    public function edit(Building $building)
    {
        $locations = Location::where('is_active', true)->get();

        return view('modules.access.buildings.edit', compact('building', 'locations'));
    }

    public function update(Request $request, Building $building)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:buildings,code,'.$building->id,
            'name' => 'required|string|max:100',
            'address' => 'nullable|string|max:255',
            'type' => 'required|in:torre,bloque,casa_independiente',
            'location_id' => 'nullable|exists:locations,id',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $building->update($validated);

        return redirect()->route('access.buildings.index')
            ->with('success', 'Torre/Bloque actualizado exitosamente.');
    }

    public function destroy(Building $building)
    {
        $building->delete();

        return redirect()->route('access.buildings.index')
            ->with('success', 'Torre/Bloque eliminado.');
    }
}
