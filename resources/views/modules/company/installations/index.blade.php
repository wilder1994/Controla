<x-company-layout title="Instalaciones">
    <x-slot:actions>
        @can('company.installations.manage')
            <x-ui.button :href="route('company.installations.create')" size="sm">+ Instalación</x-ui.button>
        @endcan
    </x-slot:actions>

    <div class="space-y-4">
        @if (($search ?? '') === '' && isset($sigBoard))
            @include('modules.ops.sig-board', ['sigBoard' => $sigBoard, 'compact' => true, 'sigLiveUrl' => route('company.sig.live')])
        @endif

        <form method="GET" action="{{ route('company.installations.index') }}"
              class="rounded-lg border border-slate-800 bg-slate-900/60 p-3 flex flex-col sm:flex-row sm:items-center gap-3">
            <div class="flex-1 min-w-0">
                <label for="q" class="sr-only">Buscar instalaciones</label>
                <input type="search" id="q" name="q" value="{{ $search }}"
                       placeholder="Buscar por nombre, código, DANE, área, cliente o personal…"
                       class="w-full h-9 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white placeholder:text-slate-600 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/30">
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <x-ui.button type="submit" size="sm" variant="secondary">Buscar</x-ui.button>
                @if ($search !== '')
                    <a href="{{ route('company.installations.index') }}" class="text-xs text-slate-500 hover:text-slate-300">Limpiar</a>
                @endif
            </div>
            <p class="text-xs text-slate-500 sm:ml-auto sm:text-right whitespace-nowrap">
                {{ $installations->total() }} {{ $installations->total() === 1 ? 'instalación' : 'instalaciones' }}
            </p>
        </form>

        <div class="rounded-lg border border-slate-800 overflow-hidden bg-slate-900/80">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-950/60 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-2.5 text-left font-medium">Instalación</th>
                            <th class="px-4 py-2.5 text-left font-medium">Código</th>
                            <th class="px-4 py-2.5 text-left font-medium hidden md:table-cell">Área</th>
                            <th class="px-4 py-2.5 text-left font-medium">Cliente</th>
                            <th class="px-4 py-2.5 text-left font-medium hidden lg:table-cell">Personal</th>
                            <th class="px-4 py-2.5 text-right font-medium">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @forelse ($installations as $installation)
                            <tr class="hover:bg-slate-800/30">
                                <td class="px-4 py-3">
                                    <p class="font-medium text-slate-200">{{ $installation->name }}</p>
                                    <p class="text-xs text-slate-500">
                                        {{ $installation->kindLabel() }}
                                        @if ($installation->city)
                                            · {{ $installation->city }}
                                        @endif
                                    </p>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-indigo-300/90">
                                    {{ $installation->code ?: '—' }}
                                    @if ($installation->dane_code)
                                        <span class="block text-slate-500">DANE {{ $installation->dane_code }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 hidden md:table-cell text-slate-400">{{ $installation->commune ?: '—' }}</td>
                                <td class="px-4 py-3 text-slate-300">{{ $installation->client?->name ?? '—' }}</td>
                                <td class="px-4 py-3 hidden lg:table-cell text-slate-400">{{ $installation->siteAdminLabel() }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('company.installations.show', $installation) }}" class="text-xs text-indigo-400 hover:text-indigo-300">Ver</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center">
                                    <p class="text-sm font-medium text-slate-300">Aún no hay instalaciones</p>
                                    <p class="text-sm text-slate-500 mt-1">Crea la primera o ábrela desde la ficha del cliente.</p>
                                    @can('company.clients.manage')
                                        <div class="mt-4">
                                            <x-ui.button :href="route('company.installations.create')" size="md">Crear instalación</x-ui.button>
                                        </div>
                                    @endcan
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($installations->hasPages())
            <div>{{ $installations->links() }}</div>
        @endif
    </div>
</x-company-layout>
