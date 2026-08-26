<?php

declare(strict_types=1);

namespace App\Http\Controllers\Resident;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\PreAuthorization;
use App\Models\Visitor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class PreAuthorizationController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        $preAuthorizations = PreAuthorization::with('visitor', 'location')
            ->where('host_id', $user->id)
            ->latest('scheduled_date')
            ->paginate(20);

        return view('modules.resident.pre_authorizations.index', compact('preAuthorizations'));
    }

    public function create(): View
    {
        $locations = Location::where('is_active', true)->get();

        return view('modules.resident.pre_authorizations.create', compact('locations'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'visitor_document' => 'required|string|max:50',
            'visitor_name' => 'required|string|max:255',
            'location_id' => 'required|exists:locations,id',
            'scheduled_date' => 'required|date|after_or_equal:today',
            'scheduled_time' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string|max:500',
        ]);

        $visitor = Visitor::firstOrCreate(
            ['document_number' => $validated['visitor_document']],
            [
                'document_type' => 'CC',
                'first_name' => explode(' ', $validated['visitor_name'])[0],
                'last_name' => substr($validated['visitor_name'], strlen(explode(' ', $validated['visitor_name'])[0]) + 1) ?: '',
                'visitor_type' => 'persona',
            ],
        );

        PreAuthorization::create([
            'visitor_id' => $visitor->id,
            'host_id' => auth()->id(),
            'location_id' => $validated['location_id'],
            'scheduled_date' => $validated['scheduled_date'],
            'scheduled_time' => $validated['scheduled_time'],
            'status' => 'pending',
            'qr_code' => Str::random(32),
            'notes' => $validated['notes'],
        ]);

        return redirect()
            ->route('resident.pre-authorizations.index')
            ->with('success', 'Pre-autorización creada.');
    }

    public function destroy(PreAuthorization $preAuthorization): RedirectResponse
    {
        if ($preAuthorization->host_id !== auth()->id()) {
            abort(403);
        }

        $preAuthorization->update(['status' => 'cancelled']);

        return redirect()
            ->route('resident.pre-authorizations.index')
            ->with('success', 'Pre-autorización cancelada.');
    }
}
