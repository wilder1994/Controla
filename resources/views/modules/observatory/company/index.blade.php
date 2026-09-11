<x-company-layout title="Observatorio">
    <div class="space-y-4">
        <div class="rounded-lg border border-slate-800 bg-slate-900/60 p-3 space-y-2">
            <p class="text-xs text-slate-500">Links para reportar. Compártelos con cada cliente (sin app ni login).</p>
            @forelse ($shareClients as $shareClient)
                @include('modules.observatory.partials.public-link', [
                    'url' => route('observatory.public.show', $shareClient->slug),
                    'name' => $shareClient->name,
                ])
            @empty
                <p class="text-sm text-slate-500">No hay clientes activos con slug.</p>
            @endforelse
        </div>

        <form method="GET" action="{{ route('company.observatory.events.index') }}"
              class="rounded-lg border border-slate-800 bg-slate-900/60 p-3 flex flex-col sm:flex-row sm:items-center gap-3">
            <div class="flex-1 min-w-0">
                <label for="q" class="sr-only">Buscar eventos</label>
                <input type="search" id="q" name="q" value="{{ $search }}"
                       placeholder="Buscar por sede, DANE, cliente o texto…"
                       class="w-full h-9 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white placeholder:text-slate-600 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/30">
            </div>
            <x-ui.button type="submit" size="sm" variant="secondary">Buscar</x-ui.button>
        </form>

        <div class="rounded-lg border border-slate-800 overflow-hidden bg-slate-900/80">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-950/60 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-2.5 text-left font-medium">Folio</th>
                        <th class="px-4 py-2.5 text-left font-medium">Sede</th>
                        <th class="px-4 py-2.5 text-left font-medium hidden md:table-cell">Cliente</th>
                        <th class="px-4 py-2.5 text-left font-medium">Estado</th>
                        <th class="px-4 py-2.5 text-right font-medium">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse ($events as $event)
                        <tr class="hover:bg-slate-800/30">
                            <td class="px-4 py-3 font-mono text-xs text-indigo-300">{{ $event->folio() }}</td>
                            <td class="px-4 py-3 text-slate-200">{{ $event->installation?->name }}</td>
                            <td class="px-4 py-3 hidden md:table-cell text-slate-400">{{ $event->client?->name }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ $event->statusLabel() }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('company.observatory.events.show', $event) }}" class="text-xs text-indigo-400 hover:text-indigo-300">Ver</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">Aún no hay reportes.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($events->hasPages())
            <div>{{ $events->links() }}</div>
        @endif
    </div>
</x-company-layout>
