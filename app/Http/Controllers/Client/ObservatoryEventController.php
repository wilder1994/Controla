<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Enums\ObservatoryEventStatus;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ObservatoryEvent;
use App\Models\ObservatoryReport;
use App\Services\Observatory\BuildObservatoryBoardService;
use App\Services\Observatory\BuildObservatoryMapService;
use App\Services\Observatory\MergeObservatoryEventsService;
use App\Services\Observatory\UnhookObservatoryReportService;
use App\Services\Observatory\UpdateObservatoryEventStatusService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class ObservatoryEventController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly UpdateObservatoryEventStatusService $statuses,
        private readonly MergeObservatoryEventsService $merger,
        private readonly UnhookObservatoryReportService $unhook,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', ObservatoryEvent::class);

        $clientId = (int) $this->tenantContext->clientId();
        abort_unless($clientId > 0, 403);
        $filters = $this->filters($request);
        $board = app(BuildObservatoryBoardService::class);
        $siteIds = $this->tenantContext->installationIds();

        $events = $board->scoped(null, $clientId, $siteIds, $filters['from'], $filters['to'])
            ->with(['installation'])
            ->when($filters['search'] !== '', function ($q) use ($filters) {
                $q->where(function ($inner) use ($filters) {
                    $inner->where('title', 'like', '%'.$filters['search'].'%')
                        ->orWhereHas('installation', fn ($i) => $i->where('name', 'like', '%'.$filters['search'].'%')
                            ->orWhere('dane_code', 'like', '%'.$filters['search'].'%'));
                });
            })
            ->when($filters['status'] !== '', fn ($q) => $q->where('status', $filters['status']))
            ->orderByDesc('opened_at')
            ->paginate(20)
            ->withQueryString();

        $client = Client::query()->findOrFail($clientId);

        return view('modules.observatory.client.index', [
            'events' => $events,
            'search' => $filters['search'],
            'from' => $filters['from'],
            'to' => $filters['to'],
            'status' => $filters['status'],
            'publicUrl' => route('observatory.public.show', $client->slug),
            'board' => $board->execute(null, $clientId, $siteIds, $filters['from'], $filters['to']),
            'map' => app(BuildObservatoryMapService::class)->execute(
                null,
                $clientId,
                $siteIds,
                'client.observatory.events.show',
            ),
        ]);
    }

    public function show(Request $request, ObservatoryEvent $event): View
    {
        $this->assertClient($event);
        $event->load(['client', 'installation', 'reports', 'statusLogs.user', 'closedBy']);
        $this->authorize('view', $event);

        $canUpdate = $request->user()?->can('update', $event) ?? false;

        return view('modules.observatory.client.show', [
            'event' => $event,
            'canUpdateStatus' => $canUpdate,
            'canMerge' => $canUpdate && $event->status !== ObservatoryEventStatus::Cerrado,
            'statuses' => ObservatoryEventStatus::options(),
            'mergeCandidates' => $this->mergeCandidates($event),
        ]);
    }

    public function updateStatus(Request $request, ObservatoryEvent $event): RedirectResponse
    {
        $this->assertClient($event);
        $this->authorize('update', $event);

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::enum(ObservatoryEventStatus::class)],
        ]);

        try {
            $this->statuses->execute(
                $event,
                ObservatoryEventStatus::from($validated['status']),
                $request->user(),
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()
            ->route('client.observatory.events.show', $event)
            ->with('success', 'Estado actualizado.');
    }

    public function merge(Request $request, ObservatoryEvent $event): RedirectResponse
    {
        $this->assertClient($event);
        $this->authorize('update', $event);

        $validated = $request->validate([
            'source_event_id' => ['required', 'integer', 'exists:observatory_events,id'],
        ]);

        $source = ObservatoryEvent::query()->findOrFail((int) $validated['source_event_id']);
        $this->assertClient($source);
        $this->authorize('update', $source);

        try {
            $this->merger->execute($event, $source);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()
            ->route('client.observatory.events.show', $event)
            ->with('success', 'Folio '.$source->folio().' unido aquí.');
    }

    public function detach(Request $request, ObservatoryEvent $event, ObservatoryReport $report): RedirectResponse
    {
        $this->assertClient($event);
        $this->authorize('update', $event);
        abort_unless((int) $report->event_id === (int) $event->id, 404);

        try {
            $created = $this->unhook->execute($event, $report);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()
            ->route('client.observatory.events.show', $event)
            ->with('success', 'Reporte pasado a '.$created->folio().'.');
    }

    /** @return \Illuminate\Support\Collection<int, ObservatoryEvent> */
    private function mergeCandidates(ObservatoryEvent $event)
    {
        return ObservatoryEvent::query()
            ->where('client_id', $event->client_id)
            ->where('installation_id', $event->installation_id)
            ->whereKeyNot($event->id)
            ->withCount('reports')
            ->orderByDesc('opened_at')
            ->limit(30)
            ->get();
    }

    private function assertClient(ObservatoryEvent $event): void
    {
        abort_unless((int) $event->client_id === (int) $this->tenantContext->clientId(), 404);
        abort_unless($this->tenantContext->allowsInstallation((int) $event->installation_id), 403);
    }

    /** @return array{search: string, from: ?string, to: ?string, status: string} */
    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'status' => ['nullable', 'string', Rule::enum(ObservatoryEventStatus::class)],
        ]);

        return [
            'search' => trim((string) ($validated['q'] ?? '')),
            'from' => $validated['from'] ?? null,
            'to' => $validated['to'] ?? null,
            'status' => (string) ($validated['status'] ?? ''),
        ];
    }
}
