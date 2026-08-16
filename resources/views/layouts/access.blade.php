<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Control de Acceso' }} — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-950 text-slate-100 overflow-hidden">
    <div class="h-screen flex overflow-hidden">
        <aside class="hidden lg:flex lg:w-64 lg:h-full lg:flex-col bg-slate-900 border-r border-slate-800 shrink-0">
            <div class="px-6 py-5 border-b border-slate-800 shrink-0">
                <p class="text-xs uppercase tracking-wider text-slate-500">Controla</p>
                <h1 class="text-lg font-semibold text-white">Control de Acceso</h1>
                @isset($activeClient)
                    <p class="text-xs text-indigo-300 mt-1">{{ $activeClient->name }}</p>
                @endisset
            </div>
<nav class="flex-1 min-h-0 px-4 py-6 space-y-1 overflow-y-auto sidebar-scroll">
                @foreach (config('access.navigation.access.items', []) as $item)
                    @can($item['permission'])
                    <a href="{{ route($item['route']) }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs(str_replace('.index', '.*', $item['route'])) || request()->routeIs($item['route']) ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                        <span>{{ $item['label'] }}</span>
                    </a>
                    @endcan
                @endforeach
                @can('client.structures.manage')
                <a href="{{ route('client.dashboard') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800 mt-4">
                    <span>Panel Conjunto</span>
                </a>
                @endcan
            </nav>
<div class="px-4 py-4 border-t border-slate-800 shrink-0">
                <p class="text-xs text-slate-500 mb-3 truncate">{{ Auth::user()->name }}</p>
                <button
                    type="button"
                    @click="$dispatch('open-panic')"
                    class="w-full flex items-center justify-between gap-2 px-3 py-2.5 rounded-lg bg-gradient-to-r from-red-950/60 to-red-900/40 hover:from-red-900/70 hover:to-red-800/50 border border-red-800/70 transition-colors group"
                >
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-red-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                        <span class="text-sm font-semibold text-red-300 group-hover:text-red-100">Botón de Pánico</span>
                    </span>
                    <svg class="w-3.5 h-3.5 text-red-500/70 group-hover:text-red-300 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </button>
            </div>
        </aside>

        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            @include('partials.operate-return-banner')
            <header class="bg-slate-900/80 border-b border-slate-800 backdrop-blur sticky top-0 z-20 shrink-0">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-slate-500">{{ now()->format('D, d M Y') }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-slate-500 hidden sm:inline">{{ Auth::user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-xs text-slate-500 hover:text-white">Salir</button>
                        </form>
                    </div>
                </div>
            </header>

            @php
                $shiftUser = Auth::user();
                $turnoService = new \App\Services\Access\TurnoService();
                $shiftRequired = config('access.shifts.enforced') && $shiftUser && ! $turnoService->isShiftOptionalFor($shiftUser);
                $activeShift = $shiftRequired ? $turnoService->currentFor($shiftUser) : null;
            @endphp
            @if($shiftRequired)
                @if($activeShift)
                    <div class="bg-emerald-900/50 border-b border-emerald-700/60">
                        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2 flex items-center justify-between gap-3">
                            <p class="text-xs text-emerald-200">
                                <span class="inline-flex items-center gap-1 font-semibold">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                    Turno activo desde las {{ $activeShift->started_at->format('H:i') }}
                                </span>
                                @if($activeShift->location)
                                    · {{ $activeShift->location->name }}
                                @endif
                            </p>
                            <form method="POST" action="{{ route('access.turnos.close') }}" class="inline" onsubmit="return confirm('¿Cerrar el turno actual?');">
                                @csrf
                                <input type="hidden" name="end_notes" value="">
                                <button type="submit" class="text-xs font-semibold text-emerald-300 hover:text-emerald-100">Cerrar turno</button>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="bg-amber-900/40 border-b border-amber-700/60">
                        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2 flex items-center justify-between gap-3">
                            <p class="text-xs text-amber-200 font-medium">No tienes un turno abierto. Para operar la portería debes iniciar tu turno.</p>
                            <a href="{{ route('access.turnos.open') }}" class="text-xs font-semibold text-amber-100 hover:text-white bg-amber-700/70 hover:bg-amber-600/80 px-3 py-1.5 rounded-lg transition-colors">Abrir turno</a>
                        </div>
                    </div>
                @endif
            @endif

            <x-ui.flash-toasts rail="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8" />

            <main class="flex-1 min-w-0 overflow-y-scroll">
                <div class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-6">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>

    @include('modules.access.partials.sidebar-rapido')

    @stack('scripts')
</body>
</html>