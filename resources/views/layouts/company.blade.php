<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Panel Empresa' }} — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-950 text-slate-100 overflow-hidden">
    @php
        $onClientsCrud = request()->routeIs('company.clients.index')
            || request()->routeIs('company.clients.show')
            || request()->routeIs('company.clients.create')
            || request()->routeIs('company.clients.edit');
        $companyContext = $companyContext ?? ['company_name' => null, 'is_quota_full' => true];
    @endphp
    <div class="h-screen flex overflow-hidden">
        <aside class="hidden lg:flex lg:w-64 lg:h-full lg:flex-col bg-slate-900 border-r border-slate-800 shrink-0">
            <div class="px-6 py-5 border-b border-slate-800 shrink-0">
                <p class="text-xs uppercase tracking-wider text-slate-500">Controla</p>
                <h1 class="text-lg font-semibold text-white">Panel Empresa</h1>
            </div>
            <nav class="flex-1 px-4 py-6 space-y-1">
                @can('company.dashboard')
                <a href="{{ route('company.dashboard') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('company.dashboard') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                    <span>Resumen</span>
                </a>
                @endcan
                @can('company.clients.view')
                <a href="{{ route('company.clients.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ $onClientsCrud ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                    <span>Clientes</span>
                </a>
                @endcan
            </nav>
            <div class="px-4 py-4 border-t border-slate-800 shrink-0">
                <p class="text-xs text-slate-400 truncate">{{ Auth::user()->name }}</p>
                <form method="POST" action="{{ route('logout') }}" class="mt-1">
                    @csrf
                    <button type="submit" class="text-xs text-slate-500 hover:text-white transition">
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </aside>

        <div class="flex-1 flex flex-col min-w-0 min-h-0 overflow-y-auto">
            <header class="bg-slate-900/80 border-b border-slate-800 backdrop-blur sticky top-0 z-10 shrink-0">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between gap-4">
                    <div class="min-w-0">
                        @isset($header)
                            {{ $header }}
                        @else
                            <h2 class="text-base font-semibold text-white truncate">{{ $title ?? 'Panel Empresa' }}</h2>
                            @if ($companyContext['company_name'])
                                <p class="text-xs text-slate-500 truncate">{{ $companyContext['company_name'] }}</p>
                            @endif
                        @endisset
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        @can('access.dashboard')
                            <x-ui.button variant="secondary" :href="route('company.porteria.enter')">
                                Portería
                            </x-ui.button>
                        @endcan
                        @can('company.clients.manage')
                            @if (! $companyContext['is_quota_full'])
                                <x-ui.button :href="route('company.clients.create')">
                                    + Conjunto
                                </x-ui.button>
                            @endif
                        @endcan
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

            <main class="flex-1 max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-5">
                {{ $slot }}
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
