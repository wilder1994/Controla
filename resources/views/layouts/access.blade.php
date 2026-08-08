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
<body class="font-sans antialiased bg-slate-950 text-slate-100">
    <div class="min-h-screen flex">
        <aside class="hidden lg:flex lg:w-64 lg:flex-col bg-slate-900 border-r border-slate-800">
            <div class="px-6 py-5 border-b border-slate-800">
                <p class="text-xs uppercase tracking-wider text-slate-500">Controla</p>
                <h1 class="text-lg font-semibold text-white">Control de Acceso</h1>
                @isset($activeClient)
                    <p class="text-xs text-indigo-300 mt-1">{{ $activeClient->name }}</p>
                @endisset
            </div>
            <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
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
            <div class="px-4 py-4 border-t border-slate-800 text-xs text-slate-500">
                {{ Auth::user()->name }}
            </div>
            <div class="px-4 pb-4 border-t border-slate-800">
                <button
                    type="button"
                    @click="$dispatch('open-panic')"
                    class="w-full flex items-center gap-2 px-3 py-2 rounded-lg bg-red-900/30 hover:bg-red-800/30 border border-red-800 text-left transition-colors group"
                >
                    <svg class="w-4 h-4 text-red-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    <span class="text-sm font-medium text-red-300 group-hover:text-red-200">Botón de Pánico</span>
                </button>
            </div>
        </aside>

        <div class="flex-1 flex flex-col min-w-0">
            <header class="bg-slate-900/80 border-b border-slate-800 backdrop-blur sticky top-0 z-20">
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

            @if (session('success'))
                <div class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 pt-4">
                    <div class="rounded-lg bg-emerald-900/40 border border-emerald-700 text-emerald-200 px-4 py-3 text-sm">{{ session('success') }}</div>
                </div>
            @endif
            @if (session('warning'))
                <div class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 pt-4">
                    <div class="rounded-lg bg-amber-900/40 border border-amber-700 text-amber-200 px-4 py-3 text-sm">{{ session('warning') }}</div>
                </div>
            @endif
            @if (session('error'))
                <div class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 pt-4">
                    <div class="rounded-lg bg-red-900/40 border border-red-700 text-red-200 px-4 py-3 text-sm">{{ session('error') }}</div>
                </div>
            @endif

            <main class="flex-1 max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-6">
                {{ $slot }}
            </main>
        </div>
    </div>

    @include('modules.access.partials.sidebar-rapido')

    @stack('scripts')
</body>
</html>
