<?php

declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StoreClientAccessPointRequest;
use App\Models\Client;
use App\Models\Location;
use App\Services\Company\ManageClientAccessPointService;
use App\Support\Platform\ActingCompanyResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ClientAccessPointController extends Controller
{
    public function __construct(
        private readonly ManageClientAccessPointService $accessPoints,
    ) {}

    public function store(StoreClientAccessPointRequest $request, Client $client): RedirectResponse
    {
        $this->assertCompany($request, $client);
        abort_unless($client->has_access, 403);

        $this->accessPoints->create($client, [
            'installation_id' => (int) $request->validated('installation_id'),
            'code' => $request->validated('code'),
            'name' => $request->validated('name'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->backToClient($client, 'Acceso creado.');
    }

    public function update(StoreClientAccessPointRequest $request, Client $client, Location $location): RedirectResponse
    {
        $this->assertPoint($request, $client, $location);

        $this->accessPoints->update($location, [
            'installation_id' => (int) $request->validated('installation_id'),
            'code' => $request->validated('code'),
            'name' => $request->validated('name'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return $this->backToClient($client, 'Acceso actualizado.');
    }

    public function destroy(Request $request, Client $client, Location $location): RedirectResponse
    {
        $this->assertPoint($request, $client, $location);
        $this->authorize('update', $client);

        $this->accessPoints->delete($location);

        return $this->backToClient($client, 'Acceso eliminado.');
    }

    private function assertPoint(Request $request, Client $client, Location $location): void
    {
        $this->assertCompany($request, $client);
        abort_unless($client->has_access, 403);
        abort_unless((int) $location->client_id === (int) $client->id, 404);
    }

    private function assertCompany(Request $request, Client $client): void
    {
        if ($request->user()?->hasRole('super-admin')) {
            return;
        }

        abort_unless(
            (int) $request->user()?->security_company_id === (int) $client->security_company_id
            || app(ActingCompanyResolver::class)->id($request->user()) === (int) $client->security_company_id,
            403
        );
    }

    private function backToClient(Client $client, string $message): RedirectResponse
    {
        return redirect()
            ->route('company.clients.show', [$client, 'vista' => 'accesos'])
            ->with('success', $message);
    }
}
