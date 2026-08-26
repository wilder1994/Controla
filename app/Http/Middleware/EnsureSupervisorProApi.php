<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureSupervisorProApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->hasRole('supervisor') || $user->security_company_id === null) {
            abort(403, 'Solo supervisores de empresa.');
        }

        $company = $user->securityCompany;
        if ($company === null || ! $company->hasSupervisionPackage()) {
            abort(403, 'La empresa no tiene Supervisión contratada.');
        }

        return $next($request);
    }
}
