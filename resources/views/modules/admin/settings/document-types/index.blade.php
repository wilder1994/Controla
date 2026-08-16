<x-admin-layout title="Ajustes">
    <div class="space-y-6">
        @include('modules.admin.settings.partials.subnav')

        <div>
            <p class="text-sm text-slate-400">Tipos de documento de identidad (CC, CE, NIT…). Aparecen en contratación y formularios de personas.</p>
        </div>

        <div class="grid lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 rounded-xl border border-slate-800 bg-slate-900 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-800">
                    <h3 class="text-sm font-semibold text-white">Tipos de documento</h3>
                </div>
                <div class="divide-y divide-slate-800">
                    @forelse ($types as $index => $type)
                        <div x-data="{ editing: false }" class="px-4 py-3">
                            <div x-show="!editing" class="flex flex-wrap items-center justify-between gap-3">
                                <div class="flex flex-wrap items-center gap-3 text-sm">
                                    <span class="text-white font-medium">{{ $type->name }}</span>
                                    <code class="text-xs text-violet-300">{{ $type->code }}</code>
                                    <span class="text-xs px-2 py-0.5 rounded-full {{ $type->is_active ? 'bg-emerald-900/40 text-emerald-300' : 'bg-rose-900/40 text-rose-300' }}">
                                        {{ $type->is_active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <form method="POST" action="{{ route('admin.settings.document-types.move-up', $type) }}">
                                        @csrf
                                        <button type="submit" @disabled($index === 0) class="text-xs text-slate-400 hover:text-white disabled:opacity-30 disabled:pointer-events-none" title="Subir">↑</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.settings.document-types.move-down', $type) }}">
                                        @csrf
                                        <button type="submit" @disabled($index === $types->count() - 1) class="text-xs text-slate-400 hover:text-white disabled:opacity-30 disabled:pointer-events-none" title="Bajar">↓</button>
                                    </form>
                                    <button type="button" @click="editing = true" class="text-xs text-indigo-300 hover:text-indigo-200">Editar</button>
                                    <form method="POST" action="{{ route('admin.settings.document-types.destroy', $type) }}" onsubmit="return confirm('¿Eliminar este tipo?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-rose-400 hover:text-rose-300">Eliminar</button>
                                    </form>
                                </div>
                            </div>

                            <form x-show="editing" x-cloak method="POST" action="{{ route('admin.settings.document-types.update', $type) }}" class="space-y-3">
                                @csrf
                                @method('PUT')
                                <div>
                                    <label class="block text-xs text-slate-400 mb-1">Nombre</label>
                                    <input type="text" name="name" value="{{ old('name', $type->name) }}" required class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                                </div>
                                <label class="inline-flex items-center gap-2 text-xs text-slate-300">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $type->is_active)) class="rounded border-slate-700 text-violet-600">
                                    Activo
                                </label>
                                <div class="flex gap-2">
                                    <button type="submit" class="rounded-lg bg-violet-600 px-3 py-2 text-xs font-semibold text-white hover:bg-violet-500">Guardar</button>
                                    <button type="button" @click="editing = false" class="rounded-lg bg-slate-800 px-3 py-2 text-xs font-semibold text-slate-300 hover:bg-slate-700">Cancelar</button>
                                </div>
                            </form>
                        </div>
                    @empty
                        <p class="px-4 py-8 text-sm text-slate-500 text-center">Sin tipos. Crea el primero.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-900 p-4 h-fit">
                <h3 class="text-sm font-semibold text-white mb-3">Nuevo tipo</h3>
                <form method="POST" action="{{ route('admin.settings.document-types.store') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Nombre</label>
                        <input type="text" name="name" value="{{ old('name') }}" required placeholder="Cédula de ciudadanía" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                    </div>
                    <label class="inline-flex items-center gap-2 text-xs text-slate-300">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="rounded border-slate-700 text-violet-600">
                        Activo
                    </label>
                    <button type="submit" class="w-full rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500">
                        Crear tipo
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
