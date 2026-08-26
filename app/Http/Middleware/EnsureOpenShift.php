<?php

namespace App\Http\Middleware;

use App\Services\Access\TurnoService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOpenShift
{
    public function __construct(private readonly TurnoService $turnoService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        if (! config('access.shifts.enforced', false)) {
            return $next($request);
        }

        if ($this->turnoService->isShiftOptionalFor($user)) {
            return $next($request);
        }

        if (! $this->turnoService->hasOpenShiftFor($user)) {
            return redirect()->route('access.turnos.open')
                ->with('error', 'Debes abrir tu turno antes de operar en la portería.');
        }

        return $next($request);
    }
}
