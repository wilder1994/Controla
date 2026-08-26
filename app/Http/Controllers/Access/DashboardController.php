<?php

namespace App\Http\Controllers\Access;

use App\Http\Controllers\Controller;
use App\Models\AccessLog;
use App\Models\Building;
use App\Models\Correspondence;
use App\Models\HousingUnit;
use App\Models\PreAuthorization;
use App\Models\Resident;
use App\Models\Visitor;

class DashboardController extends Controller
{
    public function index()
    {
        $activeEntries = AccessLog::where('status', 'active')->count();
        $todayEntries = AccessLog::whereDate('entry_time', today())->count();
        $totalVisitors = Visitor::count();
        $totalResidents = Resident::count();
        $totalHousingUnits = HousingUnit::count();
        $totalBuildings = Building::count();
        $pendingCorrespondence = Correspondence::where('status', 'pending')->count();
        $pendingPreAuthorizations = PreAuthorization::where('status', 'pending')
            ->whereDate('scheduled_date', '>=', today())
            ->count();

        $recentLogs = AccessLog::with(['visitor', 'resident', 'host', 'location'])
            ->latest('entry_time')
            ->take(10)
            ->get();

        // Chart data: daily entries for last 7 days
        $dailyLabels = [];
        $dailyData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dailyLabels[] = $date->format('D');
            $dailyData[] = AccessLog::whereDate('entry_time', $date)->count();
        }

        // Chart data: access type distribution
        $typeLabels = ['Visitante', 'Vehicular', 'Residente'];
        $typeData = [
            AccessLog::where('access_type', 'visitor')->count(),
            AccessLog::where('access_type', 'visitor_vehicle')->count(),
            AccessLog::whereIn('access_type', ['resident', 'resident_vehicle'])->count(),
        ];

        // Chart data: hourly distribution for today
        $hourlyLabels = [];
        $hourlyData = [];
        for ($h = 0; $h < 24; $h++) {
            $hourlyLabels[] = str_pad($h, 2, '0', STR_PAD_LEFT).':00';
            $hourlyData[] = AccessLog::whereDate('entry_time', today())
                ->whereTime('entry_time', '>=', str_pad($h, 2, '0').':00:00')
                ->whereTime('entry_time', '<', str_pad(($h + 1) % 24, 2, '0').':00:00')
                ->count();
        }

        return view('modules.access.dashboard', compact(
            'activeEntries', 'todayEntries', 'totalVisitors', 'totalResidents',
            'totalHousingUnits', 'totalBuildings',
            'pendingCorrespondence', 'pendingPreAuthorizations', 'recentLogs',
            'dailyLabels', 'dailyData', 'typeLabels', 'typeData',
            'hourlyLabels', 'hourlyData'
        ));
    }
}
