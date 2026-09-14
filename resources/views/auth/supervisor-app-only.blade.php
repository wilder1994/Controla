<x-auth-layout
    title="App de campo"
    subtitle="Este usuario solo opera la app de supervisión (PWA o APK). No hay panel web."
>
    <p class="text-sm text-slate-300">
        Entra con el mismo usuario y contraseña en la app de campo.
        El código de 6 dígitos sirve para firmar revista en la minuta de portería; no es el login.
    </p>

    @if ($pwaUrl !== '')
        <a
            href="{{ $pwaUrl }}"
            class="mt-5 flex w-full items-center justify-center rounded-lg bg-cyan-500 px-6 py-3 text-sm font-semibold text-slate-950 shadow-lg shadow-cyan-500/25 transition hover:bg-cyan-400"
        >
            Abrir app de campo
        </a>
    @endif

    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
        @csrf
        <button type="submit" class="text-sm font-medium text-slate-400 transition hover:text-slate-200">
            Cerrar sesión
        </button>
    </form>
</x-auth-layout>
