<?php
namespace App\Http\Controllers\Access;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\Access\AuditLogger;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user')->latest();

        if ($request->filled('action')) {
            $query->where('action', 'like', '%'.$request->action.'%');
        }

        if ($request->filled('auditable_type')) {
            $query->where('auditable_type', 'like', '%'.$request->auditable_type.'%');
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $logs = $query->paginate(30)->withQueryString();

        $actions = AuditLog::query()
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->values();

        $summary = [
            'total' => AuditLog::count(),
            'today' => AuditLog::whereDate('created_at', today())->count(),
            'entries' => AuditLog::where('action', 'like', 'access.%')->count(),
            'guard_logs' => AuditLog::where('action', 'like', 'guard_log.%')
                ->orWhere('action', 'like', 'supervision.%')->count(),
        ];

        return view('modules.access.audit.index', compact('logs', 'actions', 'summary'));
    }
}