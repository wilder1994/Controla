@php
    $operateMode = $operateMode ?? false;
    $pageTitle = $operateMode ? 'Operar portería' : 'Clientes';
@endphp

<x-company-layout :title="$pageTitle">
    <div class="space-y-4">
        @if ($operateMode)
            <div class="rounded-lg border border-emerald-800/50 bg-emerald-950/20 px-4 py-3 text-sm text-emerald-200">
                Abre un conjunto con <strong>Ver</strong> y elige <strong>Operar portería</strong> o <strong>Operar cliente</strong> desde su expediente.
            </div>
        @endif

        <form method="GET" action="{{ route('company.clients.index') }}"
              class="rounded-lg border border-slate-800 bg-slate-900/60 p-3 flex flex-col sm:flex-row sm:items-center gap-3">
            @if ($operateMode)
                <input type="hidden" name="modo" value="operar">
            @endif
            <div class="flex-1 min-w-0">
                <label for="q" class="sr-only">Buscar conjuntos</label>
                <input type="search" id="q" name="q" value="{{ $search }}"
                       placeholder="Buscar por nombre, slug o dirección…"
                       class="w-full h-9 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white placeholder:text-slate-600 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/30">
            </div>
            <div class="sm:w-40 shrink-0">
                <label for="status" class="sr-only">Estado</label>
                <select id="status" name="status"
                        class="w-full h-9 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/30">
                    @if (! $operateMode)
                        <option value="all" @selected($status === 'all')>Estado: Todos</option>
                    @endif
                    <option value="active" @selected($status === 'active')>Activos</option>
                    <option value="inactive" @selected($status === 'inactive')>Inactivos</option>
                </select>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <x-ui.button type="submit" size="sm" variant="secondary">Buscar</x-ui.button>
                @if ($search !== '' || ($operateMode ? $status !== 'active' : $status !== 'all'))
                    <a href="{{ route('company.clients.index', $operateMode ? ['modo' => 'operar'] : []) }}"
                       class="text-xs text-slate-500 hover:text-slate-300">Limpiar</a>
                @endif
            </div>
            @if ($metrics)
                <p class="text-xs text-slate-500 sm:ml-auto sm:text-right whitespace-nowrap">
                    {{ $clients->total() }} {{ $clients->total() === 1 ? 'conjunto' : 'conjuntos' }}
                    · {{ $metrics['clients_remaining'] }} cupos libres
                </p>
            @else
                <p class="text-xs text-slate-500 sm:ml-auto sm:text-right whitespace-nowrap">
                    {{ $clients->total() }} {{ $clients->total() === 1 ? 'conjunto' : 'conjuntos' }} operables
                </p>
            @endif
        </form>

        @if ($metrics && $metrics['is_quota_full'] && ! $operateMode)
            <div class="rounded-lg border border-amber-800/60 bg-amber-950/30 px-4 py-3 text-sm text-amber-200">
                Has alcanzado el cupo de tu paquete ({{ $metrics['total'] }}/{{ $metrics['max_clients'] }}).
                <a href="{{ route('company.dashboard') }}" class="text-amber-100 underline hover:no-underline">
                    Ver opciones de ampliación en Resumen
                </a>
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-lg border border-red-800 bg-red-950/40 px-4 py-3 text-sm text-red-300">{{ session('error') }}</div>
        @endif

        <div class="flex items-center justify-between gap-2">
            <h3 class="text-sm font-semibold text-white">{{ $operateMode ? 'Conjuntos disponibles' : 'Cartera' }}</h3>
            @if (! $operateMode && $metrics)
                <a href="{{ route('company.dashboard') }}" class="text-xs text-indigo-400 hover:text-indigo-300">
                    Ir al Resumen
                </a>
            @elseif ($operateMode && auth()->user()?->can('company.clients.view'))
                <a href="{{ route('company.clients.index') }}" class="text-xs text-indigo-400 hover:text-indigo-300">
                    Ver cartera completa
                </a>
            @endif
        </div>

        <div class="rounded-lg border border-slate-800 overflow-hidden bg-slate-900/80">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-950/60 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-2.5 text-left font-medium">Conjunto</th>
                            @if ($operateMode && auth()->user()?->hasRole('super-admin'))
                                <th class="px-4 py-2.5 text-left font-medium hidden lg:table-cell">Empresa</th>
                            @endif
                            <th class="px-4 py-2.5 text-left font-medium hidden md:table-cell">Dirección</th>
                            @if (! $operateMode)
                                <th class="px-4 py-2.5 text-left font-medium">Login APP</th>
                                <th class="px-4 py-2.5 text-right font-medium">Usuarios</th>
                            @endif
                            <th class="px-4 py-2.5 text-left font-medium">Estado</th>
                            <th class="px-4 py-2.5 text-right font-medium">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @forelse ($clients as $client)
                            <tr class="hover:bg-slate-800/30">
                                <td class="px-4 py-3">
                                    <p class="font-medium text-slate-200">{{ $client->name }}</p>
                                    <p class="text-xs text-slate-600">{{ $client->slug }}</p>
                                    @if ($client->address)
                                        <p class="text-xs text-slate-500 mt-1 md:hidden truncate max-w-[200px]" title="{{ $client->address }}">
                                            {{ $client->address }}
                                        </p>
                                    @endif
                                </td>
                                @if ($operateMode && auth()->user()?->hasRole('super-admin'))
                                    <td class="px-4 py-3 hidden lg:table-cell text-slate-400 text-xs">
                                        {{ $client->securityCompany?->trade_name ?? '—' }}
                                    </td>
                                @endif
                                <td class="px-4 py-3 hidden md:table-cell">
                                    @if ($client->address)
                                        <p class="text-slate-400 max-w-xs truncate" title="{{ $client->address }}">
                                            {{ $client->address }}
                                        </p>
                                    @else
                                        <span class="text-slate-600">—</span>
                                    @endif
                                </td>
                                @if (! $operateMode)
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono text-xs text-indigo-300/90">usuario{{ $client->loginDomain() }}</span>
                                            <button type="button"
                                                    class="text-xs text-slate-500 hover:text-indigo-300 copy-login"
                                                    data-login="usuario{{ $client->loginDomain() }}"
                                                    title="Copiar login">
                                                Copiar
                                            </button>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right text-slate-400 tabular-nums">
                                        {{ $client->assignments_count }}
                                    </td>
                                @endif
                                <td class="px-4 py-3">
                                    @if ($client->is_active)
                                        <span class="inline-flex rounded-full bg-emerald-900/30 px-2 py-0.5 text-xs text-emerald-300">Activo</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-slate-800 px-2 py-0.5 text-xs text-slate-500">Inactivo</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('company.clients.show', $client) }}" class="text-xs text-indigo-400 hover:text-indigo-300">Ver</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $operateMode ? (auth()->user()?->hasRole('super-admin') ? 5 : 4) : 6 }}" class="px-4 py-12 text-center">
                                    @if ($search !== '' || ($operateMode ? $status !== 'active' : $status !== 'all'))
                                        <p class="text-sm text-slate-400">No hay conjuntos que coincidan con tu búsqueda.</p>
                                        <a href="{{ route('company.clients.index', $operateMode ? ['modo' => 'operar'] : []) }}"
                                           class="inline-block mt-2 text-xs text-indigo-400 hover:text-indigo-300">
                                            Ver {{ $operateMode ? 'todos los operables' : 'toda la cartera' }}
                                        </a>
                                    @elseif ($operateMode)
                                        <p class="text-sm font-medium text-slate-300">No hay conjuntos activos para operar</p>
                                        <p class="text-sm text-slate-500 mt-1">Activa un conjunto en cartera o crea uno nuevo.</p>
                                        @can('create', App\Models\Client::class)
                                            <div class="mt-4 flex flex-wrap justify-center gap-3">
                                                @can('company.clients.view')
                                                    <x-ui.button :href="route('company.clients.index')" variant="secondary" size="md">
                                                        Ir a Clientes
                                                    </x-ui.button>
                                                @endcan
                                                @if ($metrics && ! $metrics['is_quota_full'])
                                                    <x-ui.button :href="route('company.clients.create')" size="md">
                                                        Crear conjunto
                                                    </x-ui.button>
                                                @endif
                                            </div>
                                        @endcan
                                    @else
                                        <p class="text-sm font-medium text-slate-300">Aún no tienes conjuntos en cartera</p>
                                        <p class="text-sm text-slate-500 mt-1">Crea el primero para operar portería y censo residencial.</p>
                                        @can('create', App\Models\Client::class)
                                            @if ($metrics && ! $metrics['is_quota_full'])
                                                <div class="mt-4">
                                                    <x-ui.button :href="route('company.clients.create')" size="md">
                                                        Crear conjunto
                                                    </x-ui.button>
                                                </div>
                                            @endif
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($clients->hasPages())
            <div>{{ $clients->links() }}</div>
        @endif
    </div>

    @if (! $operateMode)
        @push('scripts')
        <script>
            document.querySelectorAll('.copy-login').forEach((btn) => {
                btn.addEventListener('click', async () => {
                    const login = btn.dataset.login;
                    try {
                        await navigator.clipboard.writeText(login);
                        const prev = btn.textContent;
                        btn.textContent = 'Copiado';
                        setTimeout(() => { btn.textContent = prev; }, 1500);
                    } catch {
                        btn.textContent = 'Error';
                        setTimeout(() => { btn.textContent = 'Copiar'; }, 1500);
                    }
                });
            });
        </script>
        @endpush
    @endif
</x-company-layout>
