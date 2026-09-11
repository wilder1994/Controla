<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ForcePasswordChangeController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->user()?->must_change_password) {
            return redirect()->route('home');
        }

        return view('auth.force-password');
    }
}
