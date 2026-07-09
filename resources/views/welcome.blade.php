<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Controla — Plataforma B2B de control de accesos y vigilancia para empresas de seguridad y conjuntos residenciales.">

        <title>Controla — Control de accesos inteligente</title>

        <link rel="icon" href="{{ asset('images/branding/favicon.ico') }}" sizes="any">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-slate-100">
        <div class="relative h-screen overflow-hidden">
            <img
                src="{{ asset('images/welcome/hero-background.png') }}"
                alt=""
                aria-hidden="true"
                class="pointer-events-none fixed inset-0 h-full w-full object-cover object-center"
            >
            <div class="pointer-events-none fixed inset-0 bg-gradient-to-br from-slate-950/90 via-slate-950/75 to-slate-900/85"></div>
            <div class="pointer-events-none fixed inset-0 bg-[radial-gradient(ellipse_at_top_right,_rgba(6,182,212,0.08),_transparent_50%)]"></div>

            <div class="relative z-10 flex h-full flex-col">
                <header class="shrink-0 border-b border-white/5 bg-slate-950/50 backdrop-blur-md">
                    <div class="mx-auto flex w-full max-w-[96rem] items-center justify-between px-6 py-3 lg:px-8">
                        <a href="{{ url('/') }}" class="inline-flex shrink-0 items-center">
                            <img
                                src="{{ asset('images/branding/logo-controla.png') }}"
                                alt="Controla — WM CodeSoft"
                                class="h-11 w-auto sm:h-12 lg:h-14"
                            >
                        </a>

                        @if (Route::has('login'))
                            <nav class="flex items-center gap-3">
                                @auth
                                    <a
                                        href="{{ route('home') }}"
                                        class="rounded-lg bg-cyan-500 px-5 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-cyan-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-300"
                                    >
                                        Ir al panel
                                    </a>
                                @else
                                    <a
                                        href="{{ route('login') }}"
                                        class="rounded-lg bg-cyan-500 px-5 py-2.5 text-sm font-semibold text-slate-950 shadow-lg shadow-cyan-500/20 transition hover:bg-cyan-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-300 sm:px-6"
                                    >
                                        Iniciar sesión
                                    </a>
                                @endauth
                            </nav>
                        @endif
                    </div>
                </header>

                <main class="mx-auto flex min-h-0 w-full max-w-[96rem] flex-1 flex-col px-6 lg:px-8">
                    {{-- Hero: 40% texto / 60% imagen, ocupa el alto disponible --}}
                    <section class="grid min-h-0 flex-1 items-stretch gap-6 py-4 sm:gap-8 sm:py-5 lg:grid-cols-5 lg:gap-10 lg:py-6">
                        <div class="flex flex-col justify-center space-y-5 lg:col-span-2 lg:space-y-6">
                            <div class="space-y-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-400 sm:text-sm">
                                    Plataforma Controla
                                </p>
                                <h1 class="text-3xl font-bold leading-[1.08] text-white sm:text-4xl lg:text-5xl xl:text-[3.25rem]">
                                    Control de accesos inteligente
                                </h1>
                                <p class="text-base leading-relaxed text-slate-300 lg:text-lg xl:pr-2">
                                    Plataforma B2B para empresas de seguridad privada y conjuntos residenciales en Colombia.
                                    Portería, censo unificado y gestión multi-cliente en un solo sistema.
                                </p>
                            </div>

                            @guest
                                <div class="space-y-3">
                                    <a
                                        href="{{ route('login') }}"
                                        class="inline-flex w-full items-center justify-center rounded-lg bg-cyan-500 px-8 py-3 text-base font-semibold text-slate-950 shadow-lg shadow-cyan-500/25 transition hover:bg-cyan-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-300 sm:w-auto"
                                    >
                                        Ingresar al sistema
                                    </a>
                                    <p class="text-sm text-slate-400">
                                        Acceso para administradores, guardas y residentes autorizados.
                                        <span class="text-slate-500"> &middot; Multi-tenant &middot; Colombia</span>
                                    </p>
                                </div>
                            @endguest
                        </div>

                        <div class="relative flex min-h-0 lg:col-span-3">
                            <div class="absolute -inset-3 rounded-3xl bg-cyan-500/15 blur-3xl"></div>
                            <img
                                src="{{ asset('images/welcome/hero-dashboard.png') }}"
                                alt="Panel de administración central de Controla"
                                class="relative h-full min-h-[200px] w-full max-h-[34vh] rounded-xl border border-white/10 object-cover shadow-2xl shadow-black/50 sm:max-h-[38vh] sm:rounded-2xl lg:max-h-none lg:min-h-0 lg:rounded-2xl"
                            >
                        </div>
                    </section>

                    {{-- Cards --}}
                    <section class="shrink-0 pb-4 pt-1 sm:pb-5">
                        <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 lg:gap-5">
                            <li class="group flex gap-4 rounded-xl border border-white/10 bg-white/5 p-4 backdrop-blur-md transition duration-300 hover:border-cyan-400/30 hover:bg-white/[0.07] hover:shadow-lg hover:shadow-cyan-500/5 lg:p-5">
                                <div class="flex size-11 shrink-0 items-center justify-center rounded-lg bg-cyan-500/15 text-cyan-400 transition group-hover:bg-cyan-500/25">
                                    <svg class="size-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-base font-semibold text-cyan-300">Portería</p>
                                    <p class="mt-1 text-sm leading-snug text-slate-400">Ingreso, salida, visitantes y control vehicular en tiempo real.</p>
                                </div>
                            </li>
                            <li class="group flex gap-4 rounded-xl border border-white/10 bg-white/5 p-4 backdrop-blur-md transition duration-300 hover:border-cyan-400/30 hover:bg-white/[0.07] hover:shadow-lg hover:shadow-cyan-500/5 lg:p-5">
                                <div class="flex size-11 shrink-0 items-center justify-center rounded-lg bg-cyan-500/15 text-cyan-400 transition group-hover:bg-cyan-500/25">
                                    <svg class="size-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-base font-semibold text-cyan-300">Censo</p>
                                    <p class="mt-1 text-sm leading-snug text-slate-400">Estructuras, personas, vehículos y autorizaciones en un árbol unificado.</p>
                                </div>
                            </li>
                            <li class="group flex gap-4 rounded-xl border border-white/10 bg-white/5 p-4 backdrop-blur-md transition duration-300 hover:border-cyan-400/30 hover:bg-white/[0.07] hover:shadow-lg hover:shadow-cyan-500/5 sm:col-span-2 lg:col-span-1 lg:p-5">
                                <div class="flex size-11 shrink-0 items-center justify-center rounded-lg bg-cyan-500/15 text-cyan-400 transition group-hover:bg-cyan-500/25">
                                    <svg class="size-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-base font-semibold text-cyan-300">Multi-cliente</p>
                                    <p class="mt-1 text-sm leading-snug text-slate-400">Panel empresa, conjunto y plataforma centralizada sin Excel.</p>
                                </div>
                            </li>
                        </ul>
                    </section>
                </main>

                <footer class="shrink-0 border-t border-white/10 bg-slate-950/40 backdrop-blur-sm">
                    <div class="mx-auto flex w-full max-w-[96rem] flex-col items-center justify-between gap-1 px-6 py-3.5 text-sm text-slate-400 sm:flex-row lg:px-8">
                        <p>Controla &copy; {{ date('Y') }}</p>
                        <p>WM CodeSoft &middot; Innovative Software Solutions</p>
                    </div>
                </footer>
            </div>
        </div>
    </body>
</html>
