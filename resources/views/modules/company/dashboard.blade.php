<x-company-layout title="Resumen empresa">
    <div class="space-y-8">
        <div>
            <p class="text-slate-400 text-sm">Vista consolidada de clientes bajo tu empresa de seguridad.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="rounded-xl bg-slate-900 border border-slate-800 p-5">
                <p class="text-xs uppercase tracking-wide text-slate-500">Total clientes</p>
                <p class="mt-2 text-3xl font-bold text-white">{{ $metrics['total'] }}</p>
            </div>
            <div class="rounded-xl bg-slate-900 border border-slate-800 p-5">
                <p class="text-xs uppercase tracking-wide text-slate-500">Activos</p>
                <p class="mt-2 text-3xl font-bold text-emerald-400">{{ $metrics['active'] }}</p>
            </div>
            <div class="rounded-xl bg-slate-900 border border-slate-800 p-5">
                <p class="text-xs uppercase tracking-wide text-slate-500">Inactivos</p>
                <p class="mt-2 text-3xl font-bold text-slate-400">{{ $metrics['inactive'] }}</p>
            </div>
        </div>

        <div class="rounded-xl bg-slate-900 border border-slate-800 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between">
                <h3 class="font-semibold text-white">Clientes recientes</h3>
                @can('company.clients.manage')
                <a href="{{ route('company.clients.create') }}" class="text-sm text-indigo-400 hover:text-indigo-300">+ Nuevo cliente</a>
                @endcan
            </div>
            <ul class="divide-y divide-slate-800">
                @forelse ($recentClients as $client)
                    <li class="px-5 py-4 flex items-center justify-between gap-4">
                        <div>
                            <p class="font-medium text-white">{{ $client->name }}</p>
                            <p class="text-xs text-slate-500">Login residentes: usuario{{ $client->loginDomain() }}</p>
                        </div>
                        <a href="{{ route('company.clients.show', $client) }}" class="text-sm text-indigo-400 hover:text-indigo-300">Ver</a>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-slate-500 text-sm">Aún no hay clientes. Crea el primero desde el menú Clientes.</li>
                @endforelse
            </ul>
        </div>
    </div>
</x-company-layout>
