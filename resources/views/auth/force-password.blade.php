@php
    $inputClass = 'mt-1 block w-full rounded-lg border border-white/10 bg-slate-900/60 px-4 py-2.5 pr-10 text-sm text-white shadow-sm placeholder:text-slate-500 focus:border-cyan-400 focus:ring-cyan-400/30';
    $labelClass = 'block text-sm font-medium text-slate-300';
    $user = auth()->user();
@endphp

<x-auth-layout
    title="Primer ingreso"
    subtitle="Cambia el usuario temporal y elige una contraseña nueva para continuar."
>
    @if (session('warning'))
        <div class="mb-4 rounded-lg border border-amber-400/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-200">
            {{ session('warning') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
        @csrf
        @method('PUT')

        <div>
            <x-input-label for="current_username" value="Usuario temporal" class="{{ $labelClass }}" />
            <x-text-input
                id="current_username"
                class="{{ $inputClass }}"
                type="text"
                value="{{ $user?->username }}"
                disabled
            />
        </div>

        <div>
            <x-input-label for="username" value="Nuevo usuario" class="{{ $labelClass }}" />
            <x-text-input
                id="username"
                class="{{ $inputClass }}"
                type="text"
                name="username"
                value="{{ old('username') }}"
                required
                autofocus
                autocomplete="username"
            />
            <p class="mt-1 text-xs text-slate-500">Formato nombre.apellido.1234 (distinto al temporal).</p>
            <x-input-error :messages="$errors->updatePassword->get('username')" class="mt-2 text-sm text-red-400" />
        </div>

        <div>
            <x-input-label for="current_password" value="Contraseña actual" class="{{ $labelClass }}" />
            <x-ui.password-wrap
                id="current_password"
                class="{{ $inputClass }}"
                name="current_password"
                required
                autocomplete="current-password"
            />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2 text-sm text-red-400" />
        </div>

        <div>
            <x-input-label for="password" value="Nueva contraseña" class="{{ $labelClass }}" />
            <x-ui.password-wrap
                id="password"
                class="{{ $inputClass }}"
                name="password"
                required
                autocomplete="new-password"
            />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2 text-sm text-red-400" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Confirmar contraseña" class="{{ $labelClass }}" />
            <x-ui.password-wrap
                id="password_confirmation"
                class="{{ $inputClass }}"
                name="password_confirmation"
                required
                autocomplete="new-password"
            />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2 text-sm text-red-400" />
        </div>

        <button
            type="submit"
            class="flex w-full items-center justify-center rounded-lg bg-cyan-500 px-6 py-3 text-sm font-semibold text-slate-950 shadow-lg shadow-cyan-500/25 transition hover:bg-cyan-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-300 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900"
        >
            Guardar y continuar
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
        @csrf
        <button type="submit" class="text-sm font-medium text-slate-400 transition hover:text-slate-200">
            Cerrar sesión
        </button>
    </form>
</x-auth-layout>
