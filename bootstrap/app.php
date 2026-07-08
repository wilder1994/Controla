<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
            'password.changed' => \App\Http\Middleware\EnsurePasswordIsChanged::class,
            'tenancy.access' => \App\Http\Middleware\InitializeAccessTenancy::class,
            'tenant.unscoped' => \App\Http\Middleware\DisableTenantScoping::class,
            'company' => \App\Http\Middleware\EnsureCompanyUser::class,
            'client.admin' => \App\Http\Middleware\EnsureClientAdmin::class,
            'platform.admin' => \App\Http\Middleware\EnsurePlatformAdmin::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Sesión expirada. Recarga la página e intenta de nuevo.'], 419);
            }

            return redirect()->route('login')
                ->with('status', 'Tu sesión expiró. Vuelve a intentar el inicio de sesión.');
        });
    })->create();
