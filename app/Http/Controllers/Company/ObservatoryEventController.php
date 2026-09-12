<?php

declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Enums\ObservatoryEventStatus;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ObservatoryEvent;
use App\Services\Observatory\BuildObservatoryBoardService;
use App\Services\Observatory\BuildObservatoryMapService;
use App\Support\Platform\ActingCompanyResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class ObservatoryEventController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ObservatoryEvent::class);

        $companyId = app(ActingCompanyResolver::class)->requireId($request->user());
        $filters = $this->filters($request);
        $board = app(BuildObservatoryBoardService::class);

        $events = $board->scoped($companyId, null, null, $filters['from'], $filters['to'])
            ->with(['client', 'installation', 'latestReport'])
            ->when($filters['search'] !== '', function ($q) use ($filters) {
                $q->where(function ($inner) use ($filters) {
                    $inner->where('title', 'like', '%'.$filters['search'].'%')
                        ->orWhereHas('installation', fn ($i) => $i->where('name', 'like', '%'.$filters['search'].'%')
                            ->orWhere('dane_code', 'like', '%'.$filters['search'].'%'))
                        ->orWhereHas('client', fn ($c) => $c->where('name', 'like', '%'.$filters['search'].'%'));
                });
            })
            ->when($filters['status'] !== '', fn ($q) => $q->where('status', $filters['status']))
            ->orderByDesc('opened_at')
            ->paginate(20)
            ->withQueryString();

        $shareClients = Client::query()
            ->where('security_company_id', $companyId)
            ->where('is_active', true)
            ->whereNotNull('slug')
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return view('modules.observatory.company.index', [
            'events' => $events,
            'search' => $filters['search'],
            'from' => $filters['from'],
            'to' => $filters['to'],
            'status' => $filters['status'],
            'vista' => $filters['vista'],
            'shareClients' => $shareClients,
            'board' => $board->execute($companyId, null, null, $filters['from'], $filters['to']),
            'map' => app(BuildObservatoryMapService::class)->execute(
                $companyId,
                null,
                null,
                'company.observatory.events.show',
            ),
        ]);
    }

    public function show(Request $request, ObservatoryEvent $event): View
    {
        $event->load(['client', 'installation', 'reports.reportedBy', 'statusLogs.user', 'closedBy']);
        $this->authorize('view', $event);

        return view('modules.observatory.company.show', [
            'event' => $event,
            'canUpdateStatus' => $request->user()?->can('update', $event) ?? false,
            'folioMap' => app(BuildObservatoryMapService::class)->forEvent($event),
        ]);
    }

    /** @return array{search: string, from: ?string, to: ?string, status: string, vista: string} */
    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'status' => ['nullable', 'string', Rule::enum(ObservatoryEventStatus::class)],
            'vista' => ['nullable', 'string', Rule::in(['tablero', 'eventos'])],
        ]);

        return [
            'search' => trim((string) ($validated['q'] ?? '')),
            'from' => $validated['from'] ?? null,
            'to' => $validated['to'] ?? null,
            'status' => (string) ($validated['status'] ?? ''),
            'vista' => (string) ($validated['vista'] ?? 'tablero'),
        ];
    }
}
