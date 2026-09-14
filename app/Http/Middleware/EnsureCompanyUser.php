<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Support\Platform\SupportCompanyContext;
use Symfony\Component\HttpFoundation\Response;

final class EnsureCompanyUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        if ($user->hasRole('super-admin')) {
            if (! SupportCompanyContext::isActive() && ! $this->allowsSuperAdminWithoutSupport($request)) {
                return redirect()
                    ->route('admin.dashboard')
                    ->with('warning', SupportCompanyContext::EXPIRED_FLASH);
            }

            return $next($request);
        }

        if ($user->hasAnyRole(['company-admin', 'colaborador']) && $user->security_company_id) {
            return $next($request);
        }

        if ($request->routeIs('company.clients.index') && $request->query('modo') === 'operar') {
            if ($user->can('access.dashboard') && count($user->assignedClientIds()) > 0) {
                return $next($request);
            }
        }

        abort(403, 'Acceso restringido al panel de empresa.');
    }

    private function allowsSuperAdminWithoutSupport(Request $request): bool
    {
        return $request->routeIs([
            'company.porteria.enter',
            'company.clients.select',
            'company.clients.index',
            'company.clients.show',
            'company.clients.activate',
            'company.clients.operate-client',
        ]);
    }
}
