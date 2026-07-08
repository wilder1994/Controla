<x-company-layout title="Clientes">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-white">Cartera de clientes</h2>
                <p class="text-sm text-slate-400 mt-1">Gestión centralizada — reemplaza el Excel de Axesa.</p>
            </div>
            @can('create', App\Models\Client::class)
            <a href="{{ route('company.clients.create') }}"
               class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                Nuevo cliente
            </a>
            @endcan
        </div>

        <div class="rounded-xl border border-slate-800 overflow-hidden bg-slate-900">
            <table class="min-w-full divide-y divide-slate-800 text-sm">
                <thead class="bg-slate-950/60">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Cliente</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Plan</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Login APP</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Estado</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse ($clients as $client)
                        <tr class="hover:bg-slate-800/40">
                            <td class="px-4 py-3">
                                <p class="font-medium text-white">{{ $client->name }}</p>
                                <p class="text-xs text-slate-500">{{ $client->slug }}</p>
                            </td>
                            <td class="px-4 py-3 text-slate-300">{{ $client->plan_tier->label() }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-indigo-300">@{{ $client->login_suffix }}</td>
                            <td class="px-4 py-3">
                                @if ($client->is_active)
                                    <span class="inline-flex rounded-full bg-emerald-900/50 px-2 py-0.5 text-xs text-emerald-300">Activo</span>
                                @else
                                    <span class="inline-flex rounded-full bg-slate-800 px-2 py-0.5 text-xs text-slate-400">Inactivo</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right space-x-3">
                                <a href="{{ route('company.clients.show', $client) }}" class="text-indigo-400 hover:text-indigo-300">Detalle</a>
                                @can('operate', $client)
                                <form action="{{ route('company.clients.activate', $client) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-emerald-400 hover:text-emerald-300">Operar</button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-slate-500">No hay clientes registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $clients->links() }}</div>
    </div>
</x-company-layout>
