<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Auth\ResolveUserHomeRoute;
use App\Support\Platform\SupportCompanyContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        private readonly ResolveUserHomeRoute $resolveUserHomeRoute,
    ) {}

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();
        $home = $this->resolveUserHomeRoute->forUser($user);

        if ($user->hasRole('super-admin')) {
            $intended = $request->session()->pull('url.intended', $home);
            if (SupportCompanyContext::isCompanyPanelUrl($intended)) {
                return redirect()
                    ->route('admin.dashboard')
                    ->with('warning', SupportCompanyContext::EXPIRED_FLASH);
            }

            return redirect()->to($intended);
        }

        return redirect()->intended($home);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
