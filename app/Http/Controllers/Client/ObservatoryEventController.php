<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Enums\ObservatoryEventStatus;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ObservatoryEvent;
use App\Services\Observatory\BuildObservatoryMapService;
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
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', ObservatoryEvent::class);

        $clientId = (int) $this->tenantContext->clientId();
        abort_unless($clientId > 0, 403);
        $search = $request->string('q')->trim()->toString();

        $events = ObservatoryEvent::query()
            ->with(['installation'])
            ->where('client_id', $clientId)
            ->when($this->tenantContext->installationIds() !== null, function ($q) {
                $q->whereIn('installation_id', $this->tenantContext->installationIds() ?? []);
            })
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', '%'.$search.'%')
                        ->orWhereHas('installation', fn ($i) => $i->where('name', 'like', '%'.$search.'%')
                            ->orWhere('dane_code', 'like', '%'.$search.'%'));
                });
            })
            ->orderByDesc('opened_at')
            ->paginate(20)
            ->withQueryString();

        $client = Client::query()->findOrFail($clientId);

        return view('modules.observatory.client.index', [
            'events' => $events,
            'search' => $search,
            'publicUrl' => route('observatory.public.show', $client->slug),
            'map' => app(BuildObservatoryMapService::class)->execute(
                null,
                $clientId,
                $this->tenantContext->installationIds(),
                'client.observatory.events.show',
            ),
        ]);
    }

    public function show(Request $request, ObservatoryEvent $event): View
    {
        $this->assertClient($event);
        $event->load(['client', 'installation', 'reports', 'statusLogs.user', 'closedBy']);
        $this->authorize('view', $event);

        return view('modules.observatory.client.show', [
            'event' => $event,
            'canUpdateStatus' => $request->user()?->can('update', $event) ?? false,
            'statuses' => ObservatoryEventStatus::options(),
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

    private function assertClient(ObservatoryEvent $event): void
    {
        abort_unless((int) $event->client_id === (int) $this->tenantContext->clientId(), 404);
        abort_unless($this->tenantContext->allowsInstallation((int) $event->installation_id), 403);
    }
}
