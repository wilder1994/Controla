<x-company-layout :title="$client->name">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div>
                <a href="{{ route('company.clients.index') }}" class="text-sm text-slate-400 hover:text-white">&larr; Clientes</a>
                <h2 class="mt-2 text-2xl font-bold text-white">{{ $client->name }}</h2>
                <p class="text-sm text-slate-400 mt-1">Slug: {{ $client->slug }} · Plan {{ $client->plan_tier->label() }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @can('operate', $client)
                <form action="{{ route('company.clients.activate', $client) }}" method="POST">
                    @csrf
                    <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">
                        Operar portería
                    </button>
                </form>
                @endcan
                @can('update', $client)
                <a href="{{ route('company.clients.edit', $client) }}" class="rounded-lg border border-slate-700 px-4 py-2 text-sm text-slate-200 hover:bg-slate-800">
                    Editar
                </a>
                @endcan
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="rounded-xl border border-slate-800 bg-slate-900 p-5">
                <p class="text-xs uppercase text-slate-500">Login residentes</p>
                <p class="mt-2 font-mono text-indigo-300">usuario{{ $client->loginDomain() }}</p>
            </div>
            <div class="rounded-xl border border-slate-800 bg-slate-900 p-5">
                <p class="text-xs uppercase text-slate-500">Capacidad plan</p>
                <p class="mt-2 text-2xl font-bold text-white">{{ $client->max_structures }} unidades</p>
            </div>
            <div class="rounded-xl border border-slate-800 bg-slate-900 p-5">
                <p class="text-xs uppercase text-slate-500">Usuarios asignados</p>
                <p class="mt-2 text-2xl font-bold text-white">{{ $client->assignments_count }}</p>
            </div>
        </div>
    </div>
</x-company-layout>
