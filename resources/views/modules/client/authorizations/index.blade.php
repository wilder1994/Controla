<x-client-layout title="Autorizaciones">
    <div class="space-y-6">
        <div class="flex flex-wrap justify-between gap-4">
            <h2 class="text-2xl font-bold text-white">Pre-autorizaciones</h2>
            <div class="flex gap-2">
                <a href="{{ route('client.authorizations.import') }}" class="rounded-lg bg-slate-800 px-4 py-2 text-sm text-white hover:bg-slate-700">Importar Excel</a>
                <a href="{{ route('client.authorizations.create') }}" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-500">Nueva</a>
            </div>
        </div>
        <div class="rounded-xl border border-slate-800 overflow-hidden bg-slate-900">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-950/60">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs uppercase text-slate-500">Visitante</th>
                        <th class="px-4 py-3 text-left text-xs uppercase text-slate-500">Unidad</th>
                        <th class="px-4 py-3 text-left text-xs uppercase text-slate-500">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs uppercase text-slate-500">Estado</th>
                        <th class="px-4 py-3 text-left text-xs uppercase text-slate-500">QR</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse ($authorizations as $auth)
                        <tr>
                            <td class="px-4 py-3 text-white">{{ $auth->visitor_name }}</td>
                            <td class="px-4 py-3 text-slate-400">{{ $auth->structure?->name }}</td>
                            <td class="px-4 py-3 text-slate-400">{{ $auth->valid_for_date->format('d/m/Y') }}</td>
                            <td class="px-4 py-3"><span class="text-xs rounded-full bg-slate-800 px-2 py-0.5">{{ $auth->status->label() }}</span></td>
                            <td class="px-4 py-3 font-mono text-xs text-indigo-300">{{ $auth->qr_auth_token }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">Sin autorizaciones.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $authorizations->links() }}
    </div>
</x-client-layout>
