<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Portal Residente' }} - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="h-full bg-slate-950 text-slate-100 antialiased">
    <div class="min-h-full flex flex-col">
        <header class="sticky top-0 z-40 bg-slate-900/80 backdrop-blur border-b border-slate-800">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-14 items-center justify-between">
                    <div class="flex items-center gap-3">
                        <a href="{{ route('resident.dashboard') }}" class="text-sm font-bold text-teal-400">{{ config('app.name') }}</a>
                        <span class="text-xs text-slate-600">|</span>
                        <span class="text-xs text-slate-400">Portal Residente</span>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="text-sm text-slate-400">{{ auth()->user()?->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-xs text-slate-500 hover:text-red-400">Salir</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 py-8 flex-1">
            <x-ui.flash-toasts rail="w-full mb-1" />

            <nav class="flex gap-6 mb-8 text-sm border-b border-slate-800 pb-3">
                <a href="{{ route('resident.dashboard') }}" class="{{ request()->routeIs('resident.dashboard') ? 'text-teal-400 border-b-2 border-teal-400' : 'text-slate-400 hover:text-white' }} pb-3 -mb-3">Resumen</a>
                <a href="{{ route('resident.pre-authorizations.index') }}" class="{{ request()->routeIs('resident.pre-authorizations.*') ? 'text-teal-400 border-b-2 border-teal-400' : 'text-slate-400 hover:text-white' }} pb-3 -mb-3">Pre-Autorizaciones</a>
                <a href="{{ route('resident.correspondence.index') }}" class="{{ request()->routeIs('resident.correspondence.*') ? 'text-teal-400 border-b-2 border-teal-400' : 'text-slate-400 hover:text-white' }} pb-3 -mb-3">Correspondencia</a>
                <a href="{{ route('resident.messages.inbox') }}" class="{{ request()->routeIs('resident.messages.*') ? 'text-teal-400 border-b-2 border-teal-400' : 'text-slate-400 hover:text-white' }} pb-3 -mb-3">Mensajería</a>
            </nav>

            {{ $slot }}
        </div>

        <footer class="border-t border-slate-800 py-4 text-center text-xs text-slate-600">
            {{ config('app.name') }} &copy; {{ date('Y') }}
        </footer>
    </div>
    @stack('scripts')
</body>
</html>
