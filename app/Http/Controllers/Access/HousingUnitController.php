<?php

namespace App\Http\Controllers\Access;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\HousingUnit;
use Illuminate\Http\Request;

class HousingUnitController extends Controller
{
    public function index()
    {
        $units = HousingUnit::with('building', 'residents')->latest()->paginate(15);

        return view('modules.access.housing_units.index', compact('units'));
    }

    public function create()
    {
        $buildings = Building::where('is_active', true)->get();

        return view('modules.access.housing_units.create', compact('buildings'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'unit_number' => 'required|string|max:20',
            'floor' => 'nullable|string|max:10',
            'type' => 'required|in:apartamento,casa,local_comercial',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $exists = HousingUnit::where('building_id', $validated['building_id'])
            ->where('unit_number', $validated['unit_number'])->exists();
        if ($exists) {
            return back()->withInput()->withErrors(['unit_number' => 'Ya existe esta unidad en esa torre/bloque.']);
        }

        HousingUnit::create($validated);

        return redirect()->route('access.housing_units.index')
            ->with('success', 'Apartamento/Casa creado exitosamente.');
    }

    public function edit(HousingUnit $housingUnit)
    {
        $buildings = Building::where('is_active', true)->get();

        return view('modules.access.housing_units.edit', compact('housingUnit', 'buildings'));
    }

    public function update(Request $request, HousingUnit $housingUnit)
    {
        $validated = $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'unit_number' => 'required|string|max:20',
            'floor' => 'nullable|string|max:10',
            'type' => 'required|in:apartamento,casa,local_comercial',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $exists = HousingUnit::where('building_id', $validated['building_id'])
            ->where('unit_number', $validated['unit_number'])
            ->where('id', '!=', $housingUnit->id)->exists();
        if ($exists) {
            return back()->withInput()->withErrors(['unit_number' => 'Ya existe esta unidad en esa torre/bloque.']);
        }

        $housingUnit->update($validated);

        return redirect()->route('access.housing_units.index')
            ->with('success', 'Apartamento/Casa actualizado exitosamente.');
    }

    public function destroy(HousingUnit $housingUnit)
    {
        $housingUnit->delete();

        return redirect()->route('access.housing_units.index')
            ->with('success', 'Apartamento/Casa eliminado.');
    }

    public function searchByBuildingJson(Building $building)
    {
        $units = $building->housingUnits()->where('is_active', true)->get(['id', 'unit_number', 'floor', 'type']);

        return response()->json($units);
    }
}
