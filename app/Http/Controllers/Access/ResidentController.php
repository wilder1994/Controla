<?php
namespace App\Http\Controllers\Access;

use App\Http\Controllers\Controller;
use App\Models\Resident;
use App\Models\HousingUnit;
use App\Models\Building;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResidentController extends Controller
{
    public function index()
    {
        $residents = Resident::with('housingUnits.building')->latest()->paginate(15);
        return view('modules.access.residents.index', compact('residents'));
    }

    public function create()
    {
        $buildings = Building::where('is_active', true)->get();
        $housingUnits = HousingUnit::where('is_active', true)->with('building')->get();
        $housingUnitsData = $housingUnits->map(fn($u) => [
            'id' => $u->id,
            'unit_number' => $u->unit_number,
            'building_name' => $u->building->name ?? '',
            'type' => $u->type,
        ])->values();
        return view('modules.access.residents.create', compact('buildings', 'housingUnits', 'housingUnitsData'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'document_type' => 'required|string|max:20',
            'document_number' => 'required|string|max:50|unique:residents,document_number',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'birth_date' => 'nullable|date',
            'blood_type' => 'nullable|string|max:5',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'resident_type' => 'required|in:propietario,inquilino,familiar,empleado_domestico',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'housing_units' => 'nullable|array',
            'housing_units.*' => 'exists:housing_units,id',
            'primary_unit' => 'nullable|exists:housing_units,id',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $resident = Resident::create($validated);

        if ($request->filled('housing_units')) {
            $pivotData = [];
            foreach ($request->housing_units as $unitId) {
                $pivotData[$unitId] = [
                    'is_primary' => $unitId == $request->primary_unit,
                    'relationship_type' => $validated['resident_type'],
                ];
            }
            $resident->housingUnits()->sync($pivotData);
        }

        return redirect()->route('access.residents.index')
            ->with('success', 'Residente creado exitosamente.');
    }

    public function show(Resident $resident)
    {
        $resident->load(['housingUnits.building', 'vehicles', 'accessLogs' => function ($q) {
            $q->latest()->take(20);
        }]);
        return view('modules.access.residents.show', compact('resident'));
    }

    public function edit(Resident $resident)
    {
        $buildings = Building::where('is_active', true)->get();
        $housingUnits = HousingUnit::where('is_active', true)->with('building')->get();
        $housingUnitsData = $housingUnits->map(fn($u) => [
            'id' => $u->id,
            'unit_number' => $u->unit_number,
            'building_name' => $u->building->name ?? '',
            'type' => $u->type,
        ])->values();
        $resident->load('housingUnits');
        $residentUnitIds = $resident->housingUnits->pluck('id')->map(fn($id) => (string) $id)->values();
        $primaryUnit = $resident->housingUnits->where('pivot.is_primary', true)->first();
        $primaryUnitId = $primaryUnit ? (string) $primaryUnit->id : '';
        return view('modules.access.residents.edit', compact('resident', 'buildings', 'housingUnits', 'housingUnitsData', 'residentUnitIds', 'primaryUnitId'));
    }

    public function update(Request $request, Resident $resident)
    {
        $validated = $request->validate([
            'document_type' => 'required|string|max:20',
            'document_number' => 'required|string|max:50|unique:residents,document_number,' . $resident->id,
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'birth_date' => 'nullable|date',
            'blood_type' => 'nullable|string|max:5',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'resident_type' => 'required|in:propietario,inquilino,familiar,empleado_domestico',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'housing_units' => 'nullable|array',
            'housing_units.*' => 'exists:housing_units,id',
            'primary_unit' => 'nullable|exists:housing_units,id',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $resident->update($validated);

        if ($request->has('housing_units')) {
            $pivotData = [];
            foreach (($request->housing_units ?? []) as $unitId) {
                $pivotData[$unitId] = [
                    'is_primary' => $unitId == $request->primary_unit,
                    'relationship_type' => $validated['resident_type'],
                ];
            }
            $resident->housingUnits()->sync($pivotData);
        }

        return redirect()->route('access.residents.index')
            ->with('success', 'Residente actualizado exitosamente.');
    }

    public function destroy(Resident $resident)
    {
        $resident->delete();
        return redirect()->route('access.residents.index')
            ->with('success', 'Residente eliminado.');
    }

    public function searchJson(Request $request)
    {
        $query = $request->get('q');
        $residents = Resident::with('housingUnits.building')
            ->where(function ($q) use ($query) {
                $q->where('document_number', 'like', "%{$query}%")
                  ->orWhere('first_name', 'like', "%{$query}%")
                  ->orWhere('last_name', 'like', "%{$query}%")
                  ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', "%{$query}%");
            })
            ->where('is_active', true)
            ->take(10)
            ->get();

        return response()->json($residents);
    }

    public function searchHousingUnitsJson(Request $request)
    {
        $query = $request->get('q');
        $units = HousingUnit::with('building')
            ->where(function ($q) use ($query) {
                $q->where('unit_number', 'like', "%{$query}%")
                  ->orWhereHas('building', function ($b) use ($query) {
                      $b->where('name', 'like', "%{$query}%");
                  });
            })
            ->where('is_active', true)
            ->take(10)
            ->get()
            ->map(function ($u) {
                return [
                    'id' => $u->id,
                    'label' => $u->full_label,
                    'unit_number' => $u->unit_number,
                    'building_name' => $u->building->name ?? '',
                ];
            });

        return response()->json($units);
    }

    public function addVehicle(Request $request, Resident $resident)
    {
        $validated = $request->validate([
            'plate' => 'required|string|max:20|unique:vehicles,plate',
            'brand' => 'nullable|string|max:50',
            'model' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:30',
            'type' => 'required|in:carro,moto,camion',
        ]);

        $validated['resident_id'] = $resident->id;
        Vehicle::create($validated);

        return back()->with('success', 'Vehículo asignado al residente.');
    }

    public function removeVehicle(Resident $resident, Vehicle $vehicle)
    {
        if ($vehicle->resident_id !== $resident->id) {
            return back()->with('error', 'El vehículo no pertenece a este residente.');
        }
        $vehicle->update(['resident_id' => null]);
        return back()->with('success', 'Vehículo desasignado del residente.');
    }
}
