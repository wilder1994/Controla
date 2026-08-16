<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Panel Conjunto' }} — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-950 text-slate-100 overflow-hidden">
    <div class="h-screen flex overflow-hidden">
        <aside class="hidden lg:flex lg:w-64 lg:h-full lg:flex-col bg-slate-900 border-r border-slate-800 shrink-0">
            <div class="px-6 py-5 border-b border-slate-800 shrink-0">
                <p class="text-xs uppercase tracking-wider text-slate-500">Controla</p>
                <h1 class="text-lg font-semibold text-white">Panel Conjunto</h1>
                @isset($activeClient)
                    <p class="text-xs text-indigo-300 mt-1">{{ $activeClient->name }}</p>
                @endisset
            </div>
            <nav class="flex-1 px-4 py-6 space-y-1">
                @foreach (config('access.navigation.client.items', []) as $item)
                    @can($item['permission'])
                    <a href="{{ route($item['route']) }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs(str_replace('.index', '.*', $item['route'])) || request()->routeIs($item['route']) ? 'bg-teal-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                        <span>{{ $item['label'] }}</span>
                    </a>
                    @endcan
                @endforeach
                @can('access.dashboard')
                <a href="{{ route('access.dashboard') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800 mt-4">
                    <span>Consola portería</span>
                </a>
                @endcan
            </nav>
            <div class="px-4 py-4 border-t border-slate-800 text-xs text-slate-500 shrink-0">
                {{ Auth::user()->name }}
            </div>
        </aside>

        <div class="flex-1 flex flex-col min-w-0 min-h-0 overflow-y-auto">
            @include('partials.operate-return-banner')
            <header class="bg-slate-900/80 border-b border-slate-800 backdrop-blur sticky top-0 z-10 shrink-0">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between gap-4">
                    <div>
                        @isset($header)
                            {{ $header }}
                        @else
                            <h2 class="text-xl font-semibold text-white">{{ $title ?? 'Panel Conjunto' }}</h2>
                        @endisset
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm text-slate-400 hover:text-white">Cerrar sesión</button>
                    </form>
                </div>
            </header>

            <x-ui.flash-toasts rail="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8" />

            <main class="flex-1 max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-8">
                {{ $slot }}
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
