<?php

declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ObservatoryEvent;
use App\Support\Platform\ActingCompanyResolver;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ObservatoryEventController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ObservatoryEvent::class);

        $companyId = app(ActingCompanyResolver::class)->requireId($request->user());
        $search = $request->string('q')->trim()->toString();

        $events = ObservatoryEvent::query()
            ->with(['client', 'installation'])
            ->whereHas('client', fn ($q) => $q->where('security_company_id', $companyId))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', '%'.$search.'%')
                        ->orWhereHas('installation', fn ($i) => $i->where('name', 'like', '%'.$search.'%')
                            ->orWhere('dane_code', 'like', '%'.$search.'%'))
                        ->orWhereHas('client', fn ($c) => $c->where('name', 'like', '%'.$search.'%'));
                });
            })
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
            'search' => $search,
            'shareClients' => $shareClients,
        ]);
    }

    public function show(Request $request, ObservatoryEvent $event): View
    {
        $event->load(['client', 'installation', 'reports', 'statusLogs.user', 'closedBy']);
        $this->authorize('view', $event);

        return view('modules.observatory.company.show', [
            'event' => $event,
            'canUpdateStatus' => $request->user()?->can('update', $event) ?? false,
        ]);
    }
}
