<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Observatory\StoreObservatoryReportTypeRequest;
use App\Models\Client;
use App\Models\ObservatoryReportType;
use App\Services\Observatory\EnsureObservatoryReportTypesService;
use App\Support\Auth\AssignableRoles;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ObservatoryReportTypeController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly EnsureObservatoryReportTypesService $ensure,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', \App\Models\ObservatoryEvent::class);
        $client = $this->client();
        $this->ensure->execute($client);

        return view('modules.observatory.client.types', [
            'types' => $client->observatoryReportTypes()->get(),
            'nextColor' => $this->ensure->nextColor($client),
            'canEdit' => $this->canEdit($request),
            'vista' => 'tipos',
        ]);
    }

    public function store(StoreObservatoryReportTypeRequest $request): RedirectResponse
    {
        $this->assertEditor($request);
        $client = $this->client();
        $this->ensure->execute($client);
        $data = $request->validated();

        ObservatoryReportType::query()->create([
            'client_id' => $client->id,
            'name' => trim((string) $data['name']),
            'slug' => $this->ensure->uniqueSlug($client, (string) $data['name']),
            'level' => (int) $data['level'],
            'color' => strtolower((string) $data['color']),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) $client->observatoryReportTypes()->max('sort_order') + 1,
        ]);

        return back()->with('success', 'Tipo creado. Ya aparece en el link y en el mapa.');
    }

    public function update(StoreObservatoryReportTypeRequest $request, ObservatoryReportType $type): RedirectResponse
    {
        $this->assertEditor($request);
        $this->assertOwned($type);
        $data = $request->validated();
        $activeCount = ObservatoryReportType::query()
            ->where('client_id', $type->client_id)
            ->where('is_active', true)
            ->whereKeyNot($type->id)
            ->count();

        $active = $request->boolean('is_active', true);
        if (! $active && $activeCount === 0) {
            return back()->withErrors(['is_active' => 'Deja al menos un tipo activo.']);
        }

        $type->update([
            'name' => trim((string) $data['name']),
            'level' => (int) $data['level'],
            'color' => strtolower((string) $data['color']),
            'is_active' => $active,
        ]);

        return back()->with('success', 'Tipo actualizado.');
    }

    public function destroy(Request $request, ObservatoryReportType $type): RedirectResponse
    {
        $this->assertEditor($request);
        $this->assertOwned($type);

        if ($type->reports()->exists()) {
            return back()->withErrors(['type' => 'Tiene reportes. Desactívalo en vez de borrarlo.']);
        }

        $activeCount = ObservatoryReportType::query()
            ->where('client_id', $type->client_id)
            ->where('is_active', true)
            ->whereKeyNot($type->id)
            ->count();
        if ($type->is_active && $activeCount === 0) {
            return back()->withErrors(['type' => 'Deja al menos un tipo activo.']);
        }

        $type->delete();

        return back()->with('success', 'Tipo eliminado.');
    }

    private function client(): Client
    {
        $clientId = (int) $this->tenantContext->clientId();
        abort_unless($clientId > 0, 403);

        return Client::query()->findOrFail($clientId);
    }

    private function assertOwned(ObservatoryReportType $type): void
    {
        abort_unless((int) $type->client_id === (int) $this->tenantContext->clientId(), 404);
    }

    private function canEdit(Request $request): bool
    {
        $user = $request->user();

        return $user !== null
            && $user->hasRole(AssignableRoles::CLIENT_ADMIN)
            && $user->can('observatory.events.update');
    }

    private function assertEditor(Request $request): void
    {
        $this->authorize('viewAny', \App\Models\ObservatoryEvent::class);
        abort_unless($this->canEdit($request), 403);
    }
}
