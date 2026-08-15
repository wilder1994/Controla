<?php
namespace App\Http\Controllers\Access;

use App\Http\Controllers\Controller;
use App\Models\Correspondence;
use App\Models\User;
use App\Models\Location;
use App\Models\Visitor;
use App\Models\Resident;
use App\Models\HousingUnit;
use App\Notifications\CorrespondenciaRecibida;
use App\Services\Access\AuditLogger;
use Illuminate\Http\Request;

class CorrespondenceController extends Controller
{
    public function index()
    {
        $correspondence = Correspondence::with(['host', 'location', 'receiver', 'housingUnit.building', 'resident'])
            ->latest('received_at')
            ->paginate(15);

        return view('modules.access.correspondence.index', compact('correspondence'));
    }

    public function create()
    {
        $hosts = User::all();
        $locations = Location::where('is_active', true)->get();
        $visitors = Visitor::all();
        $residents = Resident::where('is_active', true)->with('housingUnits.building')->get();
        $housingUnits = HousingUnit::where('is_active', true)->with('building')->get();
        return view('modules.access.correspondence.create', compact('hosts', 'locations', 'visitors', 'residents', 'housingUnits'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'visitor_id' => 'nullable|exists:visitors,id',
            'host_id' => 'nullable|exists:users,id',
            'housing_unit_id' => 'nullable|exists:housing_units,id',
            'resident_id' => 'nullable|exists:residents,id',
            'location_id' => 'required|exists:locations,id',
            'carrier' => 'nullable|string|max:100',
            'courier_guide' => 'nullable|string|max:100',
            'package_type' => 'required|in:sobre,caja,documento,otro',
            'notes' => 'nullable|string',
        ]);

        $validated['received_at'] = now();
        $validated['received_by'] = auth()->id();
        $validated['status'] = 'pending';

        $correspondence = Correspondence::create($validated);

        if ($correspondence->host_id !== null) {
            $correspondence->host->notify(new CorrespondenciaRecibida($correspondence));
        } elseif ($correspondence->resident?->user !== null) {
            $correspondence->resident->user->notify(new CorrespondenciaRecibida($correspondence));
        }

        app(AuditLogger::class)->record($correspondence, 'correspondence.create', null, [
            'housing_unit_id' => $correspondence->housing_unit_id,
            'resident_id' => $correspondence->resident_id,
            'package_type' => $correspondence->package_type,
            'carrier' => $correspondence->carrier,
        ]);

        return redirect()->route('access.correspondence.index')
            ->with('success', 'Correspondencia registrada exitosamente.');
    }

    public function show(Correspondence $correspondence)
    {
        $correspondence->load(['host', 'location', 'receiver', 'deliverer', 'visitor', 'housingUnit.building', 'resident']);
        return view('modules.access.correspondence.show', compact('correspondence'));
    }

    public function destroy(Correspondence $correspondence)
    {
        $correspondence->delete();
        return redirect()->route('access.correspondence.index')
            ->with('success', 'Correspondencia eliminada.');
    }

    public function markDelivered(Correspondence $correspondence)
    {
        $correspondence->update([
            'delivered_at' => now(),
            'delivered_by' => auth()->id(),
            'status' => 'delivered',
        ]);

        app(AuditLogger::class)->record($correspondence, 'correspondence.deliver', null, [
            'delivered_at' => $correspondence->delivered_at?->toDateTimeString(),
            'delivered_by' => $correspondence->delivered_by,
        ]);

        return redirect()->route('access.correspondence.index')
            ->with('success', 'Correspondencia marcada como entregada.');
    }
}
