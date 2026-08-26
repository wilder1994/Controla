<?php

use App\Http\Middleware\DisableTenantScoping;
use App\Http\Middleware\EnsureClientAdmin;
use App\Http\Middleware\EnsureCompanyUser;
use App\Http\Middleware\EnsureOpenShift;
use App\Http\Middleware\EnsurePasswordIsChanged;
use App\Http\Middleware\EnsurePlatformAdmin;
use App\Http\Middleware\EnsureSupervisionUnlocked;
use App\Http\Middleware\EnsureSupervisorProApi;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\InitializeAccessTenancy;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'password.changed' => EnsurePasswordIsChanged::class,
            'tenancy.access' => InitializeAccessTenancy::class,
            'tenant.unscoped' => DisableTenantScoping::class,
            'company' => EnsureCompanyUser::class,
            'client.admin' => EnsureClientAdmin::class,
            'platform.admin' => EnsurePlatformAdmin::class,
            'permission' => PermissionMiddleware::class,
            'role' => RoleMiddleware::class,
            'supervision.unlocked' => EnsureSupervisionUnlocked::class,
            'shift.open' => EnsureOpenShift::class,
            'supervisor.pro' => EnsureSupervisorProApi::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Sesión expirada. Recarga la página e intenta de nuevo.'], 419);
            }

            return redirect()->route('login')
                ->with('status', 'Tu sesión expiró. Vuelve a intentar el inicio de sesión.');
        });
    })->create();
