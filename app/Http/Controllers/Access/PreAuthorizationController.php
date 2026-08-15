<?php
namespace App\Http\Controllers\Access;

use App\Http\Controllers\Controller;
use App\Models\PreAuthorization;
use App\Models\Visitor;
use App\Models\Location;
use App\Services\Access\AuditLogger;
use App\Services\Access\BlocklistGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PreAuthorizationController extends Controller
{
    public function index()
    {
        $preAuthorizations = PreAuthorization::with(['visitor', 'host', 'location'])
            ->latest()
            ->paginate(15);

        return view('modules.access.pre_authorizations.index', compact('preAuthorizations'));
    }

    public function create()
    {
        $locations = Location::where('is_active', true)->get();
        return view('modules.access.pre_authorizations.create', compact('locations'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'visitor_id' => 'required|exists:visitors,id',
            'location_id' => 'required|exists:locations,id',
            'scheduled_date' => 'required|date|after_or_equal:today',
            'scheduled_time' => 'nullable|date_format:H:i',
            'recurrence' => 'required|in:puntual,diario,semanal,bisemanal,mensual',
            'valid_until' => [
                $request->input('recurrence', 'puntual') !== 'puntual' ? 'required' : 'nullable',
                'date',
                'after_or_equal:scheduled_date',
            ],
            'entries_per_day' => 'nullable|integer|min:1|max:99',
            'notes' => 'nullable|string',
        ]);

        $visitorBlock = app(BlocklistGuard::class)->checkPerson(Visitor::find($validated['visitor_id']));

        if ($visitorBlock !== null) {
            return back()->withErrors(['visitor_id' => '🚫 El visitante está en lista de bloqueo: '.$visitorBlock->reason])->withInput();
        }

        $recurring = ($validated['recurrence'] ?? 'puntual') !== 'puntual';

        $validated['host_id'] = auth()->id();
        $validated['status'] = 'pending';
        $validated['qr_code'] = Str::random(40);
        $validated['entries_per_day'] = $validated['entries_per_day'] ?? 1;
        $validated['expires_at'] = ($validated['valid_until'] ?? $validated['scheduled_date']) . ' 23:59:59';

        $preAuthorization = PreAuthorization::create($validated);

        app(AuditLogger::class)->record($preAuthorization, 'pre_authorization.create', null, [
            'visitor_id' => $preAuthorization->visitor_id,
            'location_id' => $preAuthorization->location_id,
            'scheduled_date' => $preAuthorization->scheduled_date?->toDateString(),
            'recurrence' => $preAuthorization->recurrence,
            'entries_per_day' => $preAuthorization->entries_per_day,
        ]);

        return redirect()->route('access.pre_authorizations.index')
            ->with('success', 'Pre-autorización creada exitosamente.');
    }

    public function show(PreAuthorization $preAuthorization)
    {
        $preAuthorization->load(['visitor', 'host', 'location']);
        return view('modules.access.pre_authorizations.show', compact('preAuthorization'));
    }

    public function destroy(PreAuthorization $preAuthorization)
    {
        $preAuthorization->update(['status' => 'cancelled']);
        return redirect()->route('access.pre_authorizations.index')
            ->with('success', 'Pre-autorización cancelada.');
    }

    public function qr(PreAuthorization $preAuthorization)
    {
        return response()->json(['qr_code' => $preAuthorization->qr_code]);
    }
}
