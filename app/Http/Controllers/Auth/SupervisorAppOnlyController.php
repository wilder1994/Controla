<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Supervision\SupervisionAppUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class SupervisorAppOnlyController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        if (! $request->user()?->hasRole('supervisor')) {
            return redirect()->route('home');
        }

        return view('auth.supervisor-app-only', [
            'pwaUrl' => SupervisionAppUrl::pwa(),
        ]);
    }
}
