<?php

declare(strict_types=1);

namespace App\Http\Controllers\Resident;

use App\Http\Controllers\Controller;
use App\Models\AccessLog;
use App\Models\Correspondence;
use App\Models\PreAuthorization;
use App\Models\ResidentMessage;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        $preAuthorizations = PreAuthorization::with('visitor')
            ->where('host_id', $user->id)
            ->latest('scheduled_date')
            ->take(10)
            ->get();

        $pendingCorrespondence = Correspondence::with('visitor')
            ->where('host_id', $user->id)
            ->where('status', 'pending')
            ->latest('received_at')
            ->take(5)
            ->get();

        $unreadMessages = ResidentMessage::where('recipient_id', $user->id)
            ->whereNull('read_at')
            ->count();

        $activeEntries = AccessLog::where('status', 'active')->count();
        $todayEntries = AccessLog::whereDate('entry_time', today())->count();
        $monthEntries = AccessLog::whereMonth('entry_time', now()->month)
            ->whereYear('entry_time', now()->year)
            ->count();

        $activePreAuths = PreAuthorization::where('host_id', $user->id)
            ->where('status', 'pending')
            ->count();

        $unitActivity = AccessLog::with(['visitor', 'resident', 'housingUnit.building', 'location'])
            ->where('host_id', $user->id)
            ->latest('entry_time')
            ->take(10)
            ->get()
            ->map(function ($log) {
                $log->person_name = $log->visitor?->full_name ?? $log->resident?->full_name ?? '-';
                $log->person_doc = $log->visitor?->document_number ?? $log->resident?->document_number ?? '-';
                return $log;
            });

        $myNotifications = $user->notifications()->latest()->take(8)->get();

        $dailyLabels = [];
        $dailyData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dailyLabels[] = $date->format('D');
            $dailyData[] = AccessLog::whereDate('entry_time', $date)->count();
        }

        return view('modules.resident.dashboard', compact(
            'preAuthorizations', 'pendingCorrespondence', 'unreadMessages',
            'activeEntries', 'todayEntries', 'monthEntries', 'activePreAuths',
            'unitActivity', 'myNotifications',
            'dailyLabels', 'dailyData'
        ));
    }
}
