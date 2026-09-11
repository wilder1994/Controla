<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Client;
use App\Support\Client\ClientPanelModules;
use App\Support\Company\CompanyOperateContext;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureClientPanelModule
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();
        if ($user === null) {
            return redirect()->route('login');
        }

        if ($this->bypasses($user, $module)) {
            return $next($request);
        }

        $client = view()->shared('activeClient');
        if (! $client instanceof Client) {
            $clientId = app(TenantContext::class)->clientId();
            $client = $clientId ? Client::query()->find($clientId) : null;
        }

        abort_unless($client instanceof Client && $client->panelModuleEnabled($module), 403);

        return $next($request);
    }

    private function bypasses(\App\Models\User $user, string $module): bool
    {
        if ($module !== ClientPanelModules::DOORS) {
            return false;
        }

        if ($user->hasAnyRole(['guardia', 'supervisor', 'super-admin'])) {
            return true;
        }

        return $user->hasRole('company-admin')
            && CompanyOperateContext::mode() !== CompanyOperateContext::MODE_CLIENTE;
    }
}
