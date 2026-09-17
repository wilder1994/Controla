<x-access-layout title="Autorizaciones">
    <div class="rounded-xl border border-slate-800 overflow-hidden bg-slate-900">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-950/60">
                <tr>
                    <th class="px-4 py-3 text-left text-xs uppercase text-slate-500">Visitante</th>
                    <th class="px-4 py-3 text-left text-xs uppercase text-slate-500">Nodo</th>
                    <th class="px-4 py-3 text-left text-xs uppercase text-slate-500">Fecha</th>
                    <th class="px-4 py-3 text-left text-xs uppercase text-slate-500">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @forelse ($authorizations as $auth)
                    <tr>
                        <td class="px-4 py-3 text-white">{{ $auth->visitor_name }}</td>
                        <td class="px-4 py-3 text-slate-400">{{ $auth->structure?->name }}</td>
                        <td class="px-4 py-3 text-slate-400">{{ $auth->valid_for_date?->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-xs text-slate-400">{{ $auth->status?->label() ?? $auth->status }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">Sin autorizaciones.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $authorizations->links() }}
</x-access-layout>
