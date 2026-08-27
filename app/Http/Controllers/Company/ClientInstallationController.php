<?php

declare(strict_types=1);

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StoreClientInstallationRequest;
use App\Models\Client;
use App\Models\Installation;
use App\Services\Company\ManageClientInstallationService;
use App\Support\Platform\ActingCompanyResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class ClientInstallationController extends Controller
{
    public function __construct(
        private readonly ManageClientInstallationService $installations,
    ) {}

    public function store(StoreClientInstallationRequest $request, Client $client): RedirectResponse
    {
        $this->assertCompany($request, $client);

        $this->installations->create($client, [
            'name' => $request->validated('name'),
            'is_client_site' => $request->boolean('is_client_site'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->backToClient($client, $request, 'Instalación creada.');
    }

    public function update(StoreClientInstallationRequest $request, Client $client, Installation $installation): RedirectResponse
    {
        $this->assertTree($request, $client, $installation);

        $this->installations->update($installation, [
            'name' => $request->validated('name'),
            'is_client_site' => $request->boolean('is_client_site'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return $this->backToClient($client, $request, 'Instalación actualizada.');
    }

    public function destroy(Request $request, Client $client, Installation $installation): RedirectResponse
    {
        $this->assertTree($request, $client, $installation);
        $this->authorize('update', $client);

        try {
            $this->installations->delete($installation);
        } catch (ValidationException $e) {
            return $this->backToClient(
                $client,
                $request,
                $e->validator->errors()->first() ?: 'No se pudo eliminar.',
                error: true,
            );
        }

        return $this->backToClient($client, $request, 'Instalación eliminada.');
    }

    private function assertTree(Request $request, Client $client, Installation $installation): void
    {
        $this->assertCompany($request, $client);
        abort_unless((int) $installation->client_id === (int) $client->id, 404);
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

    private function backToClient(Client $client, Request $request, string $message, bool $error = false): RedirectResponse
    {
        $vista = $request->string('vista')->toString();
        if (! in_array($vista, ['accesos', 'supervision'], true)) {
            $vista = $client->has_access ? 'accesos' : 'supervision';
        }

        return redirect()
            ->route('company.clients.show', [$client, 'vista' => $vista])
            ->with($error ? 'error' : 'success', $message);
    }
}
