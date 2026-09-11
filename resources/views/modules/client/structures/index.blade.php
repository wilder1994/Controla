<x-client-layout title="Estructura">
    <div class="space-y-6">
        <div>
            <h2 class="text-2xl font-bold text-white">Estructura</h2>
            <p class="text-sm text-slate-400 mt-1">
                El censo se arma por instalación, no sobre el cliente suelto.
                @if ($client->structureType)
                    Tipo fijo: <span class="text-teal-300">{{ $client->structureType->name }}</span>.
                @else
                    <span class="text-amber-300">Sin tipo de estructura asignado en la ficha del cliente.</span>
                @endif
            </p>
        </div>

        @if ($installations->isEmpty())
            <div class="rounded-xl border border-amber-800/60 bg-amber-950/30 p-6">
                <p class="text-sm text-amber-100">Este cliente aún no tiene instalaciones. La empresa debe crearlas en la ficha (Instalaciones y puestos) antes de armar torres, salones o apartamentos.</p>
            </div>
        @else
            <form method="GET" action="{{ route('client.structures.index') }}" class="rounded-xl border border-slate-800 bg-slate-900 p-4">
                <label for="installation_id" class="block text-xs uppercase tracking-wide text-slate-500 mb-2">Seleccione instalación</label>
                <div class="flex flex-col sm:flex-row gap-3">
                    <select id="installation_id" name="installation_id" onchange="this.form.submit()"
                            class="w-full sm:max-w-md rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                        @foreach ($installations as $option)
                            <option value="{{ $option->id }}" @selected($installation?->id === $option->id)>{{ $option->name }}</option>
                        @endforeach
                    </select>
                </div>
            </form>

            @if ($installation)
                <div class="grid lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 rounded-xl border border-slate-800 bg-slate-900 p-4">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500 mb-4">Árbol de {{ $installation->name }}</h3>
                        @if ($tree->isEmpty())
                            <p class="text-slate-500 text-sm">No hay nodos en esta instalación. Crea el primero (Torre A, Salón A, etc.).</p>
                        @else
                            <x-client.structure-tree :nodes="$tree" :census="$census" />
                        @endif
                    </div>

                    <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500 mb-4">Nueva estructura</h3>
                        <form action="{{ route('client.structures.store') }}" method="POST" class="space-y-3">
                            @csrf
                            <input type="hidden" name="installation_id" value="{{ $installation->id }}">
                            <div>
                                <label class="block text-xs text-slate-400 mb-1">Nombre</label>
                                <input type="text" name="name" value="{{ old('name') }}" required placeholder="Torre A, Salón B…" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                                @error('name')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-xs text-slate-400 mb-1">Código</label>
                                <input type="text" name="code" value="{{ old('code') }}" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                            </div>
                            <div>
                                <label class="block text-xs text-slate-400 mb-1">Tipo</label>
                                <p class="w-full rounded-lg bg-slate-950/60 border border-slate-800 px-3 py-2 text-sm text-slate-300">
                                    {{ $client->structureType?->name ?? 'Sin asignar (configurar en ficha del cliente)' }}
                                </p>
                            </div>
                            <div>
                                <label class="block text-xs text-slate-400 mb-1">Padre (opcional)</label>
                                <select name="parent_id" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                                    <option value="">— Raíz de esta instalación —</option>
                                    @foreach ($parents as $parent)
                                        <option value="{{ $parent->id }}" @selected((string) old('parent_id') === (string) $parent->id)>{{ $parent->full_path }}</option>
                                    @endforeach
                                </select>
                                @error('parent_id')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
                            </div>
                            <button type="submit" class="w-full rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-500">
                                Crear estructura
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        @endif
    </div>
</x-client-layout>
