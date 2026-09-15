<x-company-layout title="Modalidades">
    @include('modules.company.settings.partials.nav-slots', ['companyNavActive' => 'modalidades'])

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 rounded-xl border border-slate-800 bg-slate-900 overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-800">
                <h3 class="text-sm font-semibold text-white">Modalidades de servicio del puesto</h3>
                <p class="text-xs text-slate-500 mt-1">Horas contratadas (1–24). El puesto elige de esta lista. No es el turno del supervisor (Ajustes → Turnos).</p>
            </div>
            <div class="divide-y divide-slate-800">
                @forelse ($modalities as $modality)
                    <div x-data="{ editing: false }" class="px-4 py-3">
                        <div x-show="!editing" class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex flex-wrap items-center gap-3 text-sm">
                                <span class="text-white font-medium">{{ $modality->label() }}</span>
                                <span class="text-xs px-2 py-0.5 rounded-full {{ $modality->is_active ? 'bg-emerald-900/40 text-emerald-300' : 'bg-rose-900/40 text-rose-300' }}">
                                    {{ $modality->is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="editing = true" class="text-xs text-indigo-300">Editar</button>
                                <form method="POST" action="{{ route('company.supervision-post-modalities.destroy', $modality) }}" onsubmit="return confirm('¿Eliminar esta modalidad?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-rose-400">Eliminar</button>
                                </form>
                            </div>
                        </div>
                        <form x-show="editing" x-cloak method="POST" action="{{ route('company.supervision-post-modalities.update', $modality) }}" class="space-y-3">
                            @csrf
                            @method('PUT')
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[11px] text-slate-500 mb-1">Horas</label>
                                    <input type="number" name="hours" min="1" max="24" value="{{ old('hours', $modality->hours) }}" required class="w-full h-9 rounded-lg bg-slate-950 border border-slate-700 px-3 text-sm text-white">
                                </div>
                                <div>
                                    <label class="block text-[11px] text-slate-500 mb-1">Nombre (opcional)</label>
                                    <input type="text" name="name" value="{{ old('name', $modality->name) }}" maxlength="80" placeholder="Diurno" class="w-full h-9 rounded-lg bg-slate-950 border border-slate-700 px-3 text-sm text-white">
                                </div>
                            </div>
                            <x-ui.field-error :messages="$errors->get('hours')" />
                            <label class="inline-flex items-center gap-2 text-xs text-slate-300">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $modality->is_active)) class="rounded border-slate-700 text-indigo-600">
                                Activo
                            </label>
                            <div class="flex gap-2">
                                <button type="submit" class="h-9 px-3 rounded-lg bg-indigo-600 text-xs font-semibold text-white">Guardar</button>
                                <button type="button" @click="editing = false" class="h-9 px-3 rounded-lg bg-slate-800 text-xs font-semibold text-slate-300">Cancelar</button>
                            </div>
                        </form>
                    </div>
                @empty
                    <p class="px-4 py-8 text-sm text-slate-500 text-center">Sin modalidades. Crea la primera (p. ej. 8, 12 o 24 h).</p>
                @endforelse
            </div>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-900 p-4 h-fit">
            <h3 class="text-sm font-semibold text-white mb-3">Nueva modalidad</h3>
            <form method="POST" action="{{ route('company.supervision-post-modalities.store') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-[11px] text-slate-500 mb-1">Horas</label>
                    <input type="number" name="hours" min="1" max="24" value="{{ old('hours') }}" required placeholder="9" class="w-full h-9 rounded-lg bg-slate-950 border border-slate-700 px-3 text-sm text-white">
                </div>
                <x-ui.field-error :messages="$errors->get('hours')" />
                <div>
                    <label class="block text-[11px] text-slate-500 mb-1">Nombre (opcional)</label>
                    <input type="text" name="name" value="{{ old('name') }}" maxlength="80" placeholder="9 h · pedido cliente" class="w-full h-9 rounded-lg bg-slate-950 border border-slate-700 px-3 text-sm text-white">
                </div>
                <label class="inline-flex items-center gap-2 text-xs text-slate-300">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="rounded border-slate-700 text-indigo-600">
                    Activo
                </label>
                <button type="submit" class="w-full h-9 rounded-lg bg-indigo-600 text-sm font-semibold text-white">Crear modalidad</button>
            </form>
        </div>
    </div>
</x-company-layout>
