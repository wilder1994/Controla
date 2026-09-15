@php
    $types = $memberTypes ?? collect();
    $openTypes = $errors->has('name');
@endphp
<div x-data="{ open: {{ $openTypes ? 'true' : 'false' }} }">
    <button type="button" @click="open = true"
            class="inline-flex rounded-lg border border-slate-700 px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-slate-800">
        Tipos de persona
    </button>
    <template x-teleport="body">
        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="open = false">
            <div class="absolute inset-0 bg-slate-950/75" @click="open = false"></div>
            <div class="relative w-full max-w-2xl max-h-[85vh] overflow-y-auto rounded-xl border border-slate-700 bg-slate-900 p-4 shadow-2xl" @click.stop>
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-slate-500">Censo</p>
                        <h3 class="text-sm font-semibold text-white">Tipos de persona</h3>
                    </div>
                    <button type="button" @click="open = false" class="text-xs text-slate-400 hover:text-white">Cerrar</button>
                </div>
                <div class="mt-3 divide-y divide-slate-800 rounded-lg border border-slate-800">
                    @forelse ($types as $type)
                        <div x-data="{ editing: false }" class="px-3 py-2.5">
                            <div x-show="!editing" class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex flex-wrap items-center gap-2 text-sm">
                                    <span class="text-white font-medium">{{ $type->name }}</span>
                                    <span class="text-xs px-2 py-0.5 rounded-full {{ $type->is_active ? 'bg-emerald-900/40 text-emerald-300' : 'bg-rose-900/40 text-rose-300' }}">
                                        {{ $type->is_active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                    <span class="text-xs text-slate-500">{{ $type->members_count }} persona{{ $type->members_count === 1 ? '' : 's' }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    @can('update', $type)
                                        <button type="button" @click="editing = true" class="text-xs text-teal-300">Editar</button>
                                    @endcan
                                    @can('delete', $type)
                                        <form method="POST" action="{{ route('client.settings.member-types.destroy', $type) }}" onsubmit="return confirm('¿Eliminar este tipo?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-rose-400">Eliminar</button>
                                        </form>
                                    @endcan
                                </div>
                            </div>
                            @can('update', $type)
                                <form x-show="editing" x-cloak method="POST" action="{{ route('client.settings.member-types.update', $type) }}" class="mt-2 space-y-2">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="name" value="{{ old('name', $type->name) }}" required class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                                    <label class="inline-flex items-center gap-2 text-xs text-slate-300">
                                        <input type="hidden" name="is_active" value="0">
                                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $type->is_active)) class="rounded border-slate-700 text-teal-600">
                                        Activo
                                    </label>
                                    <div class="flex gap-2">
                                        <button type="submit" class="rounded-lg bg-teal-600 px-3 py-1.5 text-xs font-semibold text-white">Guardar</button>
                                        <button type="button" @click="editing = false" class="rounded-lg bg-slate-800 px-3 py-1.5 text-xs text-slate-300">Cancelar</button>
                                    </div>
                                </form>
                            @endcan
                        </div>
                    @empty
                        <p class="px-3 py-6 text-sm text-slate-500">Sin tipos. Crea el primero (ej. Funcionario, Estudiante).</p>
                    @endforelse
                </div>
                @can('create', App\Models\MemberType::class)
                    <form method="POST" action="{{ route('client.settings.member-types.store') }}" class="mt-4 space-y-2">
                        @csrf
                        <p class="text-xs font-medium text-slate-300">Nuevo tipo</p>
                        <input type="text" name="name" value="{{ old('name') }}" required placeholder="Funcionario" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                        <x-ui.field-error :messages="$errors->get('name')" />
                        <label class="inline-flex items-center gap-2 text-xs text-slate-300">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="rounded border-slate-700 text-teal-600">
                            Activo
                        </label>
                        <button type="submit" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white">Crear tipo</button>
                    </form>
                @endcan
            </div>
        </div>
    </template>
</div>
