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
    <style>
        .company-shell-rail {
            width: 100%;
            max-width: 100%;
            margin-inline: auto;
            padding-inline: 1rem;
        }
        @media (min-width: 640px) {
            .company-shell-rail { padding-inline: 1.25rem; }
        }
        @media (min-width: 1024px) {
            .company-shell-rail { padding-inline: 1.5rem; }
        }
        @media (min-width: 1280px) {
            .company-shell-rail { padding-inline: 1.75rem; }
        }
        @media (min-width: 1536px) {
            .company-shell-rail {
                padding-inline: 2rem;
                max-width: 100rem;
            }
        }
        @media (min-width: 1920px) {
            .company-shell-rail {
                max-width: 110rem;
                padding-inline: 2.5rem;
            }
        }
    </style>
    @stack('styles')
</head>
<body class="font-sans antialiased bg-slate-950 text-slate-100 overflow-hidden">
    @php
        $onClientsCrud = request()->routeIs('company.clients.index')
            || request()->routeIs('company.clients.show')
            || request()->routeIs('company.clients.create')
            || request()->routeIs('company.clients.edit');
        $companyContext = $companyContext ?? ['company_name' => null, 'is_quota_full' => true];
        $supportMode = $supportMode ?? ['active' => false, 'company_name' => null, 'company_id' => null];
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
                    <span>Mi empresa</span>
                </a>
                <a href="{{ route('company.billing.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('company.billing.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                    <span>Facturación</span>
                </a>
                @endcan
                @can('company.clients.view')
                <a href="{{ route('company.clients.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ $onClientsCrud ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                    <span>Clientes</span>
                </a>
                @endcan
                @can('company.supervision.view')
                <a href="{{ route('company.supervision.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('company.supervision.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                    <span>Supervisión</span>
                </a>
                @endcan
                @can('company.settings.manage')
                <a href="{{ route('company.employees.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('company.employees.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                    <span>Empleados</span>
                </a>
                @endcan
                @can('company.users.assign')
                <a href="{{ route('company.users.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('company.users.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                    <span>Usuarios</span>
                </a>
                @endcan
                @can('company.settings.manage')
                @php
                    $onAjustes = request()->routeIs('company.job-titles.*')
                        || request()->routeIs('company.collaborator-types.*')
                        || request()->routeIs('company.supervision-zones.*')
                        || request()->routeIs('company.supervision-shifts.*')
                        || request()->routeIs('company.supervision-preop.*')
                        || request()->routeIs('company.supervision-document-types.*')
                        || request()->routeIs('company.supervision-control-book-types.*')
                        || request()->routeIs('company.supervision-weapon-types.*')
                        || request()->routeIs('company.supervision-weapon-brands.*')
                        || request()->routeIs('company.supervision-risk-types.*')
                        || request()->routeIs('company.supervision-alarm-types.*')
                        || request()->routeIs('company.supervision-support-types.*');
                @endphp
                <a href="{{ route('company.settings.edit') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('company.settings.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                    <span>Mis datos</span>
                </a>
                <a href="{{ route('company.job-titles.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ $onAjustes ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                    <span>Ajustes</span>
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
            @if (! empty($supportMode['active']))
                <div class="shrink-0 border-b border-amber-800/60 bg-amber-950/50">
                    <div class="company-shell-rail py-2.5 flex flex-wrap items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-amber-100 truncate">
                                Entraste como · {{ $supportMode['company_name'] ?? 'Empresa' }}
                            </p>
                            <p class="text-xs text-amber-200/70">
                                Panel empresa · súper admin en sesión de soporte · auditado
                            </p>
                        </div>
                        <form method="POST" action="{{ route('admin.support.exit') }}" class="shrink-0">
                            @csrf
                            <x-ui.button type="submit" variant="secondary" size="sm">
                                Salir al panel plataforma
                            </x-ui.button>
                        </form>
                    </div>
                </div>
            @endif
            <header class="sticky top-0 z-10 shrink-0">
                <div class="bg-slate-900/80 border-b border-slate-800 backdrop-blur">
                    <div class="company-shell-rail py-3 flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            @isset($header)
                                {{ $header }}
                            @else
                                <h2 class="text-base font-semibold text-white truncate">{{ $title ?? 'Panel Empresa' }}</h2>
                                <div class="mt-0.5 text-xs flex flex-wrap items-center gap-x-4 gap-y-0.5">
                                    @isset($subtitle)
                                        {{ $subtitle }}
                                    @elseif ($companyContext['company_name'])
                                        <span class="text-slate-500 truncate">{{ $companyContext['company_name'] }}</span>
                                    @endif
                                </div>
                            @endisset
                        </div>
                        <div class="flex items-center gap-2 shrink-0 flex-wrap justify-end">
                            @isset($actions)
                                {{ $actions }}
                            @elseif (request()->routeIs('company.clients.*') && ! request()->routeIs('company.clients.create'))
                                @can('company.clients.manage')
                                    <x-ui.button :href="route('company.clients.create')" size="sm">
                                        + Cliente
                                    </x-ui.button>
                                @endcan
                            @endif
                        </div>
                    </div>
                </div>

                @isset($headerTabs)
                    <div class="company-shell-rail flex flex-wrap items-start gap-1.5 -mt-px pt-0 pb-3">
                        {{ $headerTabs }}
                    </div>
                @endisset
            </header>

            <x-ui.flash-toasts rail="company-shell-rail" />

            <main class="company-shell-rail flex-1 w-full py-4 sm:py-5">
                {{ $slot }}
            </main>
        </div>
    </div>
    @stack('modals')
    @if (request()->routeIs('company.employees.index'))
        @include('modules.company.employees.partials.import-modal')
    @endif
    @if (request()->routeIs('company.clients.index'))
        @include('modules.company.clients.partials.import-modal')
    @endif
    @stack('scripts')
</body>
</html>
