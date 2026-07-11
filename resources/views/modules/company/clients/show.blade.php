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

        @can('assignUsers', $client)
        <div class="rounded-xl border border-slate-800 bg-slate-900 p-6 space-y-6">
            <div>
                <h3 class="text-lg font-semibold text-white">Operativos asignados</h3>
                <p class="text-sm text-slate-400 mt-1">Guardas y supervisores que pueden operar portería en este conjunto.</p>
            </div>

            @if ($client->assignments->isEmpty())
                <p class="text-sm text-slate-500">Aún no hay operativos asignados a este cliente.</p>
            @else
                <ul class="divide-y divide-slate-800 rounded-lg border border-slate-800">
                    @foreach ($client->assignments as $assignment)
                        @php $user = $assignment->user; @endphp
                        @if ($user)
                        <li class="flex items-center justify-between gap-4 px-4 py-3">
                            <div>
                                <p class="text-sm font-medium text-white">{{ $user->name }}</p>
                                <p class="text-xs text-slate-500">{{ $user->email }} · {{ $user->roles->pluck('name')->join(', ') }}</p>
                            </div>
                            @if ($user->hasAnyRole(['guardia', 'supervisor']))
                            <form action="{{ route('company.clients.unassign', [$client, $user]) }}" method="POST" onsubmit="return confirm('¿Desasignar a {{ $user->name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs text-rose-400 hover:text-rose-300">Quitar</button>
                            </form>
                            @endif
                        </li>
                        @endif
                    @endforeach
                </ul>
            @endif

            @php
                $available = $operatives->reject(fn ($user) => in_array($user->id, $assignedUserIds, true));
            @endphp

            @if ($available->isNotEmpty())
            <form action="{{ route('company.clients.assign', $client) }}" method="POST" class="space-y-4 border-t border-slate-800 pt-6">
                @csrf
                <div>
                    <label class="block text-xs text-slate-400 mb-2">Asignar operativos</label>
                    <select name="user_ids[]" multiple required class="w-full min-h-[120px] rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                        @foreach ($available as $operative)
                            <option value="{{ $operative->id }}">{{ $operative->name }} ({{ $operative->roles->first()?->name }})</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-slate-500 mt-2">Mantén Ctrl (Windows) para seleccionar varios.</p>
                </div>
                @error('user_ids')
                    <p class="text-sm text-rose-400">{{ $message }}</p>
                @enderror
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                    Asignar seleccionados
                </button>
            </form>
            @else
                <p class="text-sm text-slate-500 border-t border-slate-800 pt-4">No hay operativos disponibles sin asignar. Crea usuarios guardia/supervisor en la empresa.</p>
            @endif
        </div>
        @endcan
    </div>
</x-company-layout>
