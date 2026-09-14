<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Auth\LoginUsernameRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $mustChange = (bool) $user->must_change_password;

        $rules = [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed', 'different:current_password'],
        ];

        if ($mustChange) {
            $rules['username'] = LoginUsernameRules::forChange($user);
        }

        $validated = $request->validateWithBag('updatePassword', $rules, [
            'username.regex' => 'Use el formato nombre.apellido.1234.',
            'username.not_in' => 'El usuario debe ser distinto al temporal.',
            'username.unique' => 'Ese usuario ya existe.',
            'password.different' => 'La nueva contraseña debe ser distinta a la actual.',
        ]);

        $attributes = [
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ];

        if ($mustChange) {
            $attributes['username'] = $validated['username'];
        }

        $user->update($attributes);

        if ($mustChange) {
            return redirect()->route('home')->with('status', 'password-updated');
        }

        return back()->with('status', 'password-updated');
    }
}
