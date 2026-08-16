<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Panel Plataforma' }} — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-950 text-slate-100 overflow-hidden">
    <div class="h-screen flex overflow-hidden">
        <aside class="hidden lg:flex lg:w-64 lg:h-full lg:flex-col bg-slate-900 border-r border-slate-800 shrink-0">
            <div class="px-6 py-5 border-b border-slate-800 shrink-0">
                <p class="text-xs uppercase tracking-wider text-slate-500">Controla</p>
                <h1 class="text-lg font-semibold text-white">Panel Plataforma</h1>
                <p class="text-xs text-violet-300 mt-1">Súper Admin</p>
            </div>
            <nav class="flex-1 px-4 py-6 space-y-1">
                @foreach (config('access.navigation.admin.items', []) as $item)
                    @can($item['permission'])
                    <a href="{{ route($item['route']) }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs(str_replace('.index', '.*', $item['route'])) || request()->routeIs($item['route']) ? 'bg-violet-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                        <span>{{ $item['label'] }}</span>
                    </a>
                    @endcan
                @endforeach
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
            <header class="sticky top-0 z-10 shrink-0">
                <div class="bg-slate-900 border-b border-slate-800">
                    <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <h2 class="text-base font-semibold text-white truncate">{{ $title ?? 'Panel Plataforma' }}</h2>
                            <div class="mt-0.5 text-xs flex flex-wrap items-center gap-x-4 gap-y-0.5">
                                @isset($subtitle)
                                    {{ $subtitle }}
                                @else
                                    <span class="text-slate-500">Plataforma · Súper Admin</span>
                                @endisset
                            </div>
                        </div>
                        @isset($actions)
                            <div class="flex items-center gap-2 shrink-0 flex-wrap justify-end">
                                {{ $actions }}
                            </div>
                        @endisset
                    </div>
                </div>

                @isset($headerTabs)
                    <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 flex flex-wrap items-start gap-1.5 -mt-px pt-0 pb-3">
                        {{ $headerTabs }}
                    </div>
                @endisset
            </header>

            <x-ui.flash-toasts />

            <main class="flex-1 max-w-[1600px] mx-auto w-full px-4 sm:px-6 lg:px-8 py-4 min-h-0 flex flex-col">
                {{ $slot }}
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
