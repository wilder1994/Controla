<?php

namespace App\Http\Controllers\Access;

use App\Http\Controllers\Controller;
use App\Models\AccessLog;
use App\Models\Correspondence;
use App\Models\PreAuthorization;

class OperationsController extends Controller
{
    public function index()
    {
        $activeEntries = AccessLog::where('status', 'active')->count();
        $todayEntries = AccessLog::whereDate('entry_time', today())->count();
        $pendingCorrespondence = Correspondence::where('status', 'pending')->count();
        $pendingPreAuthorizations = PreAuthorization::where('status', 'pending')
            ->whereDate('scheduled_date', '>=', today())
            ->count();

        $peopleInside = AccessLog::with(['visitor', 'resident', 'housingUnit.building', 'location', 'vehicle', 'user'])
            ->where('status', 'active')
            ->latest('entry_time')
            ->get()
            ->map(function ($log) {
                $hoursInside = $log->entry_time->diffInHours(now());
                $log->hours_inside = $hoursInside;
                $log->alert_long_stay = $hoursInside >= (int) config('access.alerts.long_stay_hours');
                $log->person_name = $log->visitor?->full_name ?? $log->resident?->full_name ?? $log->user?->name ?? '-';
                $log->person_doc = $log->visitor && $log->visitor->document_type
                    ? $log->visitor->document_type.' '.$log->visitor->document_number
                    : ($log->resident && $log->resident->document_type
                        ? $log->resident->document_type.' '.$log->resident->document_number
                        : '-');
                $log->person_type = $log->access_type === 'visitor_vehicle' ? 'Visitante Vehicular'
                    : ($log->access_type === 'resident_vehicle' ? 'Residente Vehicular'
                    : ($log->access_type === 'resident' ? 'Residente' : 'Visitante'));
                $log->destination = $log->housingUnit?->full_label
                    ?? $log->housingUnit?->building?->name
                    ?? '-';

                return $log;
            });

        return view('modules.access.operations', compact(
            'activeEntries', 'todayEntries', 'pendingCorrespondence',
            'pendingPreAuthorizations', 'peopleInside'
        ));
    }
}
