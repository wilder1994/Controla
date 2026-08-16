<x-admin-layout title="Ajustes">
    <div class="space-y-6">
        <div>
            <p class="text-sm text-slate-400">Catálogo de tipos de estructura disponible para todos los clientes al armar su censo.</p>
        </div>

        <div class="grid lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 rounded-xl border border-slate-800 bg-slate-900 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-800">
                    <h3 class="text-sm font-semibold text-white">Tipos de estructura</h3>
                </div>
                <div class="divide-y divide-slate-800">
                    @foreach ($types as $type)
                        <div x-data="{ editing: false }" class="px-4 py-3">
                            <div x-show="!editing" class="flex flex-wrap items-center justify-between gap-3">
                                <div class="flex flex-wrap items-center gap-3 text-sm">
                                    <span class="text-slate-500 w-8 tabular-nums">{{ $type->sort_order }}</span>
                                    <code class="text-violet-300">{{ $type->code }}</code>
                                    <span class="text-white font-medium">{{ $type->name }}</span>
                                    @if ($type->description)
                                        <span class="text-slate-500">{{ $type->description }}</span>
                                    @endif
                                    <span class="text-xs px-2 py-0.5 rounded-full {{ $type->is_unit ? 'bg-teal-900/40 text-teal-300' : 'bg-slate-800 text-slate-400' }}">
                                        {{ $type->is_unit ? 'Unidad' : 'Contenedor' }}
                                    </span>
                                    <span class="text-xs px-2 py-0.5 rounded-full {{ $type->is_active ? 'bg-emerald-900/40 text-emerald-300' : 'bg-rose-900/40 text-rose-300' }}">
                                        {{ $type->is_active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                    <span class="text-xs text-slate-500">{{ $type->structures_count }} estructuras</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" @click="editing = true" class="text-xs text-indigo-300 hover:text-indigo-200">Editar</button>
                                    @if ($type->structures_count === 0)
                                        <form method="POST" action="{{ route('admin.settings.structure-types.destroy', $type) }}" onsubmit="return confirm('¿Eliminar este tipo?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-rose-400 hover:text-rose-300">Eliminar</button>
                                        </form>
                                    @endif
                                </div>
                            </div>

                            <form x-show="editing" x-cloak method="POST" action="{{ route('admin.settings.structure-types.update', $type) }}" class="grid sm:grid-cols-2 gap-3">
                                @csrf
                                @method('PUT')
                                <div>
                                    <label class="block text-xs text-slate-400 mb-1">Código</label>
                                    <input type="text" name="code" value="{{ old('code', $type->code) }}" required class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                                </div>
                                <div>
                                    <label class="block text-xs text-slate-400 mb-1">Nombre</label>
                                    <input type="text" name="name" value="{{ old('name', $type->name) }}" required class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-xs text-slate-400 mb-1">Descripción</label>
                                    <input type="text" name="description" value="{{ old('description', $type->description) }}" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                                </div>
                                <div>
                                    <label class="block text-xs text-slate-400 mb-1">Orden</label>
                                    <input type="number" name="sort_order" value="{{ old('sort_order', $type->sort_order) }}" min="0" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                                </div>
                                <div class="flex items-end gap-4 pb-1">
                                    <label class="inline-flex items-center gap-2 text-xs text-slate-300">
                                        <input type="hidden" name="is_unit" value="0">
                                        <input type="checkbox" name="is_unit" value="1" @checked(old('is_unit', $type->is_unit)) class="rounded border-slate-700 text-violet-600">
                                        Unidad ocupable
                                    </label>
                                    <label class="inline-flex items-center gap-2 text-xs text-slate-300">
                                        <input type="hidden" name="is_active" value="0">
                                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $type->is_active)) class="rounded border-slate-700 text-violet-600">
                                        Activo
                                    </label>
                                </div>
                                <div class="sm:col-span-2 flex gap-2">
                                    <button type="submit" class="rounded-lg bg-violet-600 px-3 py-2 text-xs font-semibold text-white hover:bg-violet-500">Guardar</button>
                                    <button type="button" @click="editing = false" class="rounded-lg bg-slate-800 px-3 py-2 text-xs font-semibold text-slate-300 hover:bg-slate-700">Cancelar</button>
                                </div>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-900 p-4 h-fit">
                <h3 class="text-sm font-semibold text-white mb-3">Nuevo tipo</h3>
                <form method="POST" action="{{ route('admin.settings.structure-types.store') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Código</label>
                        <input type="text" name="code" value="{{ old('code') }}" required placeholder="warehouse" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Nombre</label>
                        <input type="text" name="name" value="{{ old('name') }}" required placeholder="Bodega" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Descripción</label>
                        <input type="text" name="description" value="{{ old('description') }}" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Orden</label>
                        <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                    </div>
                    <label class="inline-flex items-center gap-2 text-xs text-slate-300">
                        <input type="checkbox" name="is_unit" value="1" @checked(old('is_unit')) class="rounded border-slate-700 text-violet-600">
                        Es unidad ocupable
                    </label>
                    <label class="inline-flex items-center gap-2 text-xs text-slate-300">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="rounded border-slate-700 text-violet-600">
                        Activo (visible para clientes)
                    </label>
                    <button type="submit" class="w-full rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500">
                        Crear tipo
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
