<x-company-layout title="Turnos">
    @include('modules.company.settings.partials.nav-slots', ['companyNavActive' => 'turnos'])

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 rounded-xl border border-slate-800 bg-slate-900 overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-800">
                <h3 class="text-sm font-semibold text-white">Catálogo de turnos</h3>
                <p class="text-xs text-slate-500 mt-1">Nombre y horario. La app de campo solo muestra los activos.</p>
            </div>
            <div class="divide-y divide-slate-800">
                @forelse ($templates as $template)
                    <div x-data="{ editing: false }" class="px-4 py-3">
                        <div x-show="!editing" class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex flex-wrap items-center gap-3 text-sm">
                                <span class="text-white font-medium">{{ $template->name }}</span>
                                <span class="text-xs text-slate-400">{{ $template->scheduleLabel() }}</span>
                                <span class="text-xs px-2 py-0.5 rounded-full {{ $template->is_active ? 'bg-emerald-900/40 text-emerald-300' : 'bg-rose-900/40 text-rose-300' }}">
                                    {{ $template->is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="editing = true" class="text-xs text-indigo-300">Editar</button>
                                <form method="POST" action="{{ route('company.supervision-shifts.destroy', $template) }}" onsubmit="return confirm('¿Eliminar este turno?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-rose-400">Eliminar</button>
                                </form>
                            </div>
                        </div>
                        <form x-show="editing" x-cloak method="POST" action="{{ route('company.supervision-shifts.update', $template) }}" class="space-y-3">
                            @csrf
                            @method('PUT')
                            <input type="text" name="name" value="{{ old('name', $template->name) }}" required class="w-full h-9 rounded-lg bg-slate-950 border border-slate-700 px-3 text-sm text-white">
                            <div class="grid grid-cols-2 gap-2">
                                <input type="time" name="starts_at" value="{{ old('starts_at', $template->starts_at) }}" required class="h-9 rounded-lg bg-slate-950 border border-slate-700 px-3 text-sm text-white">
                                <input type="time" name="ends_at" value="{{ old('ends_at', $template->ends_at) }}" required class="h-9 rounded-lg bg-slate-950 border border-slate-700 px-3 text-sm text-white">
                            </div>
                            <label class="inline-flex items-center gap-2 text-xs text-slate-300">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $template->is_active)) class="rounded border-slate-700 text-indigo-600">
                                Activo
                            </label>
                            <div class="flex gap-2">
                                <button type="submit" class="h-9 px-3 rounded-lg bg-indigo-600 text-xs font-semibold text-white">Guardar</button>
                                <button type="button" @click="editing = false" class="h-9 px-3 rounded-lg bg-slate-800 text-xs font-semibold text-slate-300">Cancelar</button>
                            </div>
                        </form>
                    </div>
                @empty
                    <p class="px-4 py-8 text-sm text-slate-500 text-center">Sin turnos. Crea el primero.</p>
                @endforelse
            </div>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-900 p-4 h-fit">
            <h3 class="text-sm font-semibold text-white mb-3">Nuevo turno</h3>
            <form method="POST" action="{{ route('company.supervision-shifts.store') }}" class="space-y-3">
                @csrf
                <input type="text" name="name" value="{{ old('name') }}" required placeholder="Día" class="w-full h-9 rounded-lg bg-slate-950 border border-slate-700 px-3 text-sm text-white">
                <div class="grid grid-cols-2 gap-2">
                    <input type="time" name="starts_at" value="{{ old('starts_at', '06:00') }}" required class="h-9 rounded-lg bg-slate-950 border border-slate-700 px-3 text-sm text-white">
                    <input type="time" name="ends_at" value="{{ old('ends_at', '18:00') }}" required class="h-9 rounded-lg bg-slate-950 border border-slate-700 px-3 text-sm text-white">
                </div>
                <x-ui.field-error :messages="$errors->get('name')" />
                <label class="inline-flex items-center gap-2 text-xs text-slate-300">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="rounded border-slate-700 text-indigo-600">
                    Activo
                </label>
                <button type="submit" class="w-full h-9 rounded-lg bg-indigo-600 text-sm font-semibold text-white">Crear turno</button>
            </form>
        </div>
    </div>
</x-company-layout>
