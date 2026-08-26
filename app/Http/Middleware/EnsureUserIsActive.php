<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && ! auth()->user()->is_active) {
            auth()->logout();

            return redirect()->route('login')->withErrors([
                'email' => 'Tu cuenta ha sido desactivada.',
            ]);
        }

        return $next($request);
    }
}
