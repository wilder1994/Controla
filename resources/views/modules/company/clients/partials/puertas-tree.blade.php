@php
    $canManageTree = $canManageTree ?? false;
    $installations = $installations ?? collect();
@endphp

<section class="rounded-lg border border-slate-800 bg-slate-900/80 p-4 space-y-4">
    <div>
        <h3 class="text-sm font-semibold text-white">Puertas</h3>
        <p class="mt-1 text-xs text-slate-500">
            Quién entra y sale en cada instalación. La puerta no es un puesto: el sitio se arma en Instalaciones y puestos.
        </p>
    </div>

    <div class="space-y-3">
        @forelse ($installations as $installation)
            <article class="rounded-lg border border-slate-800 bg-slate-950/40 p-3 space-y-3">
                <div class="flex flex-wrap items-center gap-2">
                    <p class="text-sm font-medium text-white">{{ $installation->name }}</p>
                    @if ($installation->is_client_site)
                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-indigo-900/40 text-indigo-300">Mismo cliente</span>
                    @endif
                    <span class="text-xs text-slate-500">{{ $installation->locations->count() }} puerta{{ $installation->locations->count() === 1 ? '' : 's' }}</span>
                </div>

                <ul class="space-y-2">
                    @forelse ($installation->locations as $location)
                        <li class="rounded-md border border-slate-800 px-3 py-2" x-data="{ editingPoint: false }">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm text-slate-200">
                                    <span class="font-mono text-xs text-slate-500">{{ $location->code }}</span>
                                    {{ $location->name }}
                                    <span class="text-[10px] {{ $location->is_active ? 'text-emerald-400' : 'text-rose-400' }}">{{ $location->is_active ? 'activo' : 'inactivo' }}</span>
                                </p>
                                @if ($canManageTree)
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="editingPoint = !editingPoint" class="text-xs text-indigo-300">Editar</button>
                                        <form method="POST" action="{{ route('company.clients.locations.destroy', [$client, $location]) }}" onsubmit="return confirm('¿Eliminar esta puerta?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-rose-400">Eliminar</button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                            @if ($canManageTree)
                                <form x-show="editingPoint" x-cloak method="POST" action="{{ route('company.clients.locations.update', [$client, $location]) }}" class="mt-2 grid sm:grid-cols-4 gap-2">
                                    @csrf
                                    @method('PUT')
                                    <select name="installation_id" class="rounded-lg bg-slate-950 border border-slate-700 px-2 py-1.5 text-xs text-white">
                                        @foreach ($installations as $option)
                                            <option value="{{ $option->id }}" @selected($option->id === $location->installation_id)>{{ $option->name }}</option>
                                        @endforeach
                                    </select>
                                    <input type="text" name="code" value="{{ $location->code }}" required class="rounded-lg bg-slate-950 border border-slate-700 px-2 py-1.5 text-xs text-white">
                                    <input type="text" name="name" value="{{ $location->name }}" required class="rounded-lg bg-slate-950 border border-slate-700 px-2 py-1.5 text-xs text-white">
                                    <div class="flex items-center gap-2">
                                        <label class="inline-flex items-center gap-1 text-[11px] text-slate-300">
                                            <input type="hidden" name="is_active" value="0">
                                            <input type="checkbox" name="is_active" value="1" @checked($location->is_active) class="rounded border-slate-700 text-indigo-600">
                                            Activo
                                        </label>
                                        <button type="submit" class="rounded-lg bg-indigo-600 px-2 py-1 text-[11px] font-semibold text-white">OK</button>
                                    </div>
                                </form>
                            @endif
                        </li>
                    @empty
                        <li class="text-xs text-slate-500">Sin puertas en esta instalación.</li>
                    @endforelse
                </ul>

                @if ($canManageTree)
                    <form method="POST" action="{{ route('company.clients.locations.store', $client) }}" class="grid sm:grid-cols-4 gap-2 items-end">
                        @csrf
                        <input type="hidden" name="installation_id" value="{{ $installation->id }}">
                        <div>
                            <label class="block text-[11px] text-slate-500 mb-1">Código</label>
                            <input type="text" name="code" required placeholder="PA-01" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-2 py-1.5 text-xs text-white">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-[11px] text-slate-500 mb-1">Nueva puerta</label>
                            <input type="text" name="name" required placeholder="Puerta principal" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-2 py-1.5 text-xs text-white">
                        </div>
                        <button type="submit" class="rounded-lg bg-slate-800 px-3 py-1.5 text-xs font-semibold text-slate-200 hover:bg-slate-700">Agregar puerta</button>
                    </form>
                @endif
            </article>
        @empty
            <p class="text-sm text-slate-500">
                Aún no hay instalaciones.
                <a href="{{ route('company.clients.show', [$client, 'vista' => 'sitio']) }}" class="text-indigo-300 hover:text-indigo-200">Créela en Instalaciones y puestos</a>
                y vuelva a las puertas.
            </p>
        @endforelse
    </div>
</section>
