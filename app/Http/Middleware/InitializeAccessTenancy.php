<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Client;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class InitializeAccessTenancy
{
    public function __construct(
        private readonly TenantContext $tenantContext,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        $sessionKey = config('tenancy.session.active_client_key');
        $requestedClientId = $request->route('client')
            ? (int) $request->route('client')
            : ($request->session()->get($sessionKey) ? (int) $request->session()->get($sessionKey) : null);

        if ($request->has('client_id')) {
            $requestedClientId = (int) $request->query('client_id');
        }

        $this->tenantContext->hydrateForUser($user, $requestedClientId);

        if ($this->tenantContext->clientId() === null) {
            $allowedIds = $user->assignedClientIds();

            if (count($allowedIds) === 0) {
                abort(403, 'No tienes clientes asignados para operar portería.');
            }

            if (count($allowedIds) === 1) {
                $this->tenantContext->setClientId($allowedIds[0]);
                $request->session()->put($sessionKey, $allowedIds[0]);
            } else {
                return redirect()
                    ->route('company.clients.select')
                    ->with('warning', 'Selecciona el conjunto en el que vas a operar.');
            }
        }

        $client = Client::query()->find($this->tenantContext->clientId());

        if ($client === null || ! $user->canAccessClient((int) $client->id)) {
            abort(403, 'No tienes permiso para operar en este cliente.');
        }

        $request->session()->put($sessionKey, $client->id);
        view()->share('activeClient', $client);

        return $next($request);
    }
}
