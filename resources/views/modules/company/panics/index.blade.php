<x-company-layout title="Atención de pánicos">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <p class="text-sm text-slate-400">Fichas abiertas al atender. Quien tomó el caso las cierra.</p>
        <form method="GET" class="flex gap-2">
            <select name="status" class="h-9 px-2 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white">
                <option value="">Todos</option>
                <option value="abierto" @selected($status === 'abierto')>Abiertos</option>
                <option value="cerrado" @selected($status === 'cerrado')>Cerrados</option>
            </select>
            <button type="submit" class="h-9 px-3 rounded-lg border border-slate-700 text-sm text-slate-200">Filtrar</button>
        </form>
    </div>

    <div class="rounded-xl border border-slate-800 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-900 text-[11px] uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="text-left px-3 py-2 font-medium">Folio</th>
                    <th class="text-left px-3 py-2 font-medium">Quién activó</th>
                    <th class="text-left px-3 py-2 font-medium">Sitio</th>
                    <th class="text-left px-3 py-2 font-medium">Atiende</th>
                    <th class="text-left px-3 py-2 font-medium">Estado</th>
                    <th class="text-right px-3 py-2 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800 bg-slate-950/40">
                @forelse ($attentions as $row)
                    <tr>
                        <td class="px-3 py-2 font-mono text-slate-200">{{ $row->folio() }}</td>
                        <td class="px-3 py-2 text-slate-300">{{ $row->alert?->actor?->name ?? '—' }}</td>
                        <td class="px-3 py-2 text-slate-400">
                            {{ $row->alert?->installation?->name ?? $row->alert?->client?->name ?? '—' }}
                        </td>
                        <td class="px-3 py-2 text-slate-300">{{ $row->attendee?->name }}</td>
                        <td class="px-3 py-2">
                            <span class="text-xs {{ $row->isOpen() ? 'text-amber-300' : 'text-emerald-300' }}">
                                {{ $row->status->label() }}
                            </span>
                        </td>
                        <td class="px-3 py-2 text-right">
                            <a href="{{ route('company.panics.show', $row) }}" class="text-indigo-400 hover:text-indigo-300">Ver</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-3 py-8 text-center text-slate-500">Aún no hay atenciones de pánico.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if ($attentions->hasPages())
            <div class="px-3 py-3 border-t border-slate-800">{{ $attentions->links() }}</div>
        @endif
    </div>
</x-company-layout>
