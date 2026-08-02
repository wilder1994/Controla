<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\ResolveUserHomeRoute;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View|RedirectResponse
    {
        if (! config('billing.allow_public_register', false)) {
            return redirect()
                ->route('planes.index')
                ->with('warning', 'El registro público está deshabilitado. Contrata un plan o contacta a soporte.');
        }

        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        if (! config('billing.allow_public_register', false)) {
            abort(403);
        }
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(app(ResolveUserHomeRoute::class)->forUser($user));
    }
}
