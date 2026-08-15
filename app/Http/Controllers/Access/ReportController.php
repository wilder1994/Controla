<?php
namespace App\Http\Controllers\Access;

use App\Http\Controllers\Controller;
use App\Models\AccessLog;
use App\Models\Visitor;
use App\Models\Location;
use App\Exports\AccessLogsExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $query = AccessLog::with(['visitor', 'host', 'location', 'resident']);

        if ($request->filled('date_from')) {
            $query->whereDate('entry_time', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('entry_time', '<=', $request->date_to);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }
        if ($request->filled('access_type')) {
            $query->where('access_type', $request->access_type);
        }

        $logs = $query->latest('entry_time')->paginate(20)->withQueryString();

        $totalEntries = AccessLog::count();
        $activeEntries = AccessLog::where('status', 'active')->count();
        $todayEntries = AccessLog::whereDate('entry_time', today())->count();
        $totalVisitors = Visitor::count();
        $avgDuration = AccessLog::whereNotNull('exit_time')
            ->get(['entry_time', 'exit_time'])
            ->map(fn ($log) => $log->exit_time?->diffInMinutes($log->entry_time))
            ->filter()
            ->avg();

        $locations = Location::where('is_active', true)->get();

        // Chart data: daily entries for last 14 days
        $dailyLabels = [];
        $dailyData = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dailyLabels[] = $date->format('d/m');
            $dailyData[] = AccessLog::whereDate('entry_time', $date)->count();
        }

        // Chart data: top locations
        $topLocations = AccessLog::selectRaw('location_id, COUNT(*) as total')
            ->groupBy('location_id')
            ->orderByDesc('total')
            ->take(5)
            ->get();
        $locLabels = $topLocations->map(fn($l) => $l->location?->name ?? '?')->toArray();
        $locData = $topLocations->pluck('total')->toArray();

        // Chart data: access type distribution
        $typeLabels = ['Visitante', 'Vehicular', 'Residente'];
        $typeData = [
            AccessLog::where('access_type', 'visitor')->count(),
            AccessLog::where('access_type', 'visitor_vehicle')->count(),
            AccessLog::whereIn('access_type', ['resident', 'resident_vehicle'])->count(),
        ];

        return view('modules.access.reports.index', compact(
            'logs', 'totalEntries', 'activeEntries', 'todayEntries', 'totalVisitors', 'avgDuration', 'locations',
            'dailyLabels', 'dailyData', 'locLabels', 'locData', 'typeLabels', 'typeData'
        ));
    }

    public function export(Request $request): BinaryFileResponse
    {
        return Excel::download(
            new AccessLogsExport($request),
            'reporte-accesos.xlsx',
        );
    }

    public function printable(Request $request)
    {
        $query = AccessLog::with(['visitor', 'host', 'location', 'resident', 'housingUnit.building', 'vehicle', 'user'])
            ->latest('entry_time');

        if ($request->filled('date_from')) {
            $query->whereDate('entry_time', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('entry_time', '<=', $request->date_to);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }
        if ($request->filled('access_type')) {
            $query->where('access_type', $request->access_type);
        }

        $logs = $query->get();

        $total = $logs->count();
        $completed = $logs->where('status', 'completed')->count();
        $active = $logs->where('status', 'active')->count();

        $client = null;
        $clientId = app(\App\Support\Tenancy\TenantContext::class)->clientId();
        if ($clientId !== null) {
            $client = \App\Models\Client::query()->withoutGlobalScopes()->find($clientId);
        }

        return view('modules.access.reports.printable', compact('logs', 'total', 'completed', 'active', 'client'));
    }
}
