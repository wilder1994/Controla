<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureSupervisionUnlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        if ($user->isSupervisionManager()) {
            return $next($request);
        }

        if ($request->session()->get('supervision.authorized')) {
            return $next($request);
        }

        return redirect()->route('access.supervision.unlock')
            ->with('warning', 'Debe ingresar el código de supervisión para acceder a este módulo.');
    }
}
