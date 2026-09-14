<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    protected array $except = [
        'password.first',
        'password.update',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->must_change_password) {
            if (! in_array($request->route()?->getName(), $this->except)) {
                return redirect()->route('password.first')
                    ->with('warning', 'Debes cambiar tu usuario y tu contraseña antes de continuar.');
            }
        }

        return $next($request);
    }
}
