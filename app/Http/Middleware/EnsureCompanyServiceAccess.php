<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Company\CompanyServiceAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureCompanyServiceAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null || $user->hasRole('super-admin')) {
            return $next($request);
        }

        $user->loadMissing('securityCompany');
        if (! CompanyServiceAccess::isCut($user->securityCompany)) {
            return $next($request);
        }

        return response()->json(['message' => CompanyServiceAccess::DENIED], 403);
    }
}
