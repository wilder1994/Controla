<?php

namespace App\Http\Controllers\Access;

use App\Http\Controllers\Controller;
use App\Models\Blocklist;
use App\Models\Resident;
use App\Models\Vehicle;
use App\Models\Visitor;
use App\Services\Access\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlocklistController extends Controller
{
    public function index(): View
    {
        $entries = Blocklist::with(['blocker', 'client'])
            ->active()
            ->latest('blocked_at')
            ->paginate(20);

        return view('modules.access.blocklist.index', compact('entries'));
    }

    public function create(): View
    {
        $types = [
            'visitor' => 'Visitante',
            'vehicle' => 'Vehículo',
            'resident' => 'Residente',
        ];

        return view('modules.access.blocklist.create', compact('types'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'blockable_type' => 'required|in:visitor,vehicle,resident',
            'blockable_id' => 'required|integer',
            'reason' => 'required|string|max:255',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $entry = Blocklist::create([
            'client_id' => session('active_client_id'),
            'blockable_type' => $validated['blockable_type'],
            'blockable_id' => $validated['blockable_id'],
            'reason' => $validated['reason'],
            'blocked_by' => auth()->id(),
            'blocked_at' => now(),
            'expires_at' => $validated['expires_at'],
            'is_active' => true,
        ]);

        app(AuditLogger::class)->record($entry, 'blocklist.create', null, [
            'blockable_type' => $entry->blockable_type,
            'blockable_id' => $entry->blockable_id,
            'reason' => $entry->reason,
        ]);

        return redirect()->route('access.blocklist.index')
            ->with('success', 'Entrada agregada a la lista de bloqueo.');
    }

    public function searchJson(Request $request): JsonResponse
    {
        $type = $request->get('type', 'visitor');
        $query = $request->get('q');

        if (! $query || strlen($query) < 2) {
            return response()->json([]);
        }

        $results = match ($type) {
            'vehicle' => Vehicle::where('plate', 'like', "%{$query}%")
                ->limit(10)->get(['id', 'plate', 'brand', 'model', 'color']),
            'resident' => Resident::where(function ($q) use ($query): void {
                $q->where('document_number', 'like', "%{$query}%")
                    ->orWhere('first_name', 'like', "%{$query}%")
                    ->orWhere('last_name', 'like', "%{$query}%");
            })->limit(10)->get(['id', 'document_type', 'document_number', 'first_name', 'last_name']),
            default => Visitor::where(function ($q) use ($query): void {
                $q->where('document_number', 'like', "%{$query}%")
                    ->orWhere('first_name', 'like', "%{$query}%")
                    ->orWhere('last_name', 'like', "%{$query}%");
            })->limit(10)->get(['id', 'document_type', 'document_number', 'first_name', 'last_name', 'company']),
        };

        return response()->json($results);
    }

    public function destroy(Blocklist $blocklist): RedirectResponse
    {
        $blocklist->update(['is_active' => false]);

        app(AuditLogger::class)->record($blocklist, 'blocklist.remove', [
            'is_active' => true,
        ], [
            'is_active' => false,
            'reason' => $blocklist->reason,
        ]);

        return redirect()->route('access.blocklist.index')
            ->with('success', 'Bloqueo removido.');
    }
}
