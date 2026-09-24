<?php

namespace App\Http\Middleware;

use App\Support\Company\CompanyServiceAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();

        if (! $user->is_active) {
            return $this->denyWeb($request, 'Tu cuenta ha sido desactivada.');
        }

        if ($user->hasRole('super-admin')) {
            return $next($request);
        }

        $user->loadMissing('securityCompany');
        if (! CompanyServiceAccess::isCut($user->securityCompany)) {
            return $next($request);
        }

        if (CompanyServiceAccess::allowsWebRequest($user, $request)) {
            view()->share('companyServiceSuspended', true);

            return $next($request);
        }

        if (CompanyServiceAccess::mayBrowseWhileCut($user)) {
            view()->share('companyServiceSuspended', true);
            abort(403, CompanyServiceAccess::DENIED);
        }

        return $this->denyWeb($request, CompanyServiceAccess::DENIED);
    }

    private function denyWeb(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }

        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors([
            'email' => $message,
        ]);
    }
}
