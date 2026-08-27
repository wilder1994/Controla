<x-company-layout title="Marcas de arma">
    @include('modules.company.settings.partials.nav-slots', ['companyNavActive' => 'marcas'])

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 rounded-xl border border-slate-800 bg-slate-900 overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-800">
                <h3 class="text-sm font-semibold text-white">Marcas de arma</h3>
                <p class="text-xs text-slate-500 mt-1">Catálogo para la revista de armamento. Solo las activas salen en la app.</p>
            </div>
            <div class="divide-y divide-slate-800">
                @forelse ($brands as $brand)
                    <div x-data="{ editing: false }" class="px-4 py-3">
                        <div x-show="!editing" class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex flex-wrap items-center gap-3 text-sm">
                                <span class="text-white font-medium">{{ $brand->name }}</span>
                                <span class="text-xs px-2 py-0.5 rounded-full {{ $brand->is_active ? 'bg-emerald-900/40 text-emerald-300' : 'bg-rose-900/40 text-rose-300' }}">
                                    {{ $brand->is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="editing = true" class="text-xs text-indigo-300">Editar</button>
                                <form method="POST" action="{{ route('company.supervision-weapon-brands.destroy', $brand) }}" onsubmit="return confirm('¿Eliminar esta marca?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-rose-400">Eliminar</button>
                                </form>
                            </div>
                        </div>
                        <form x-show="editing" x-cloak method="POST" action="{{ route('company.supervision-weapon-brands.update', $brand) }}" class="space-y-3">
                            @csrf
                            @method('PUT')
                            <input type="text" name="name" value="{{ old('name', $brand->name) }}" required class="w-full h-9 rounded-lg bg-slate-950 border border-slate-700 px-3 text-sm text-white">
                            <label class="inline-flex items-center gap-2 text-xs text-slate-300">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $brand->is_active)) class="rounded border-slate-700 text-indigo-600">
                                Activo
                            </label>
                            <div class="flex gap-2">
                                <button type="submit" class="h-9 px-3 rounded-lg bg-indigo-600 text-xs font-semibold text-white">Guardar</button>
                                <button type="button" @click="editing = false" class="h-9 px-3 rounded-lg bg-slate-800 text-xs font-semibold text-slate-300">Cancelar</button>
                            </div>
                        </form>
                    </div>
                @empty
                    <p class="px-4 py-8 text-sm text-slate-500 text-center">Sin marcas. Crea la primera; el combo de la app queda vacío hasta entonces.</p>
                @endforelse
            </div>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-900 p-4 h-fit">
            <h3 class="text-sm font-semibold text-white mb-3">Nueva marca</h3>
            <form method="POST" action="{{ route('company.supervision-weapon-brands.store') }}" class="space-y-3">
                @csrf
                <input type="text" name="name" value="{{ old('name') }}" required placeholder="Glock" class="w-full h-9 rounded-lg bg-slate-950 border border-slate-700 px-3 text-sm text-white">
                <x-ui.field-error :messages="$errors->get('name')" />
                <label class="inline-flex items-center gap-2 text-xs text-slate-300">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="rounded border-slate-700 text-indigo-600">
                    Activo
                </label>
                <button type="submit" class="w-full h-9 rounded-lg bg-indigo-600 text-sm font-semibold text-white">Crear marca</button>
            </form>
        </div>
    </div>
</x-company-layout>
