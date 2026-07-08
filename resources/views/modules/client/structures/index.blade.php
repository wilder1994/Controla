<x-client-layout title="Residencial">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-white">Estructura residencial</h2>
                <p class="text-sm text-slate-400 mt-1">Árbol de unidades con badges de censo — §1.2.1 Residencial.</p>
            </div>
        </div>

        <div class="grid lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 rounded-xl border border-slate-800 bg-slate-900 p-4">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500 mb-4">Árbol del conjunto</h3>
                @if ($tree->isEmpty())
                    <p class="text-slate-500 text-sm">No hay estructuras registradas. Crea la primera unidad.</p>
                @else
                    <x-client.structure-tree :nodes="$tree" :census="$census" />
                @endif
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500 mb-4">Nueva estructura</h3>
                <form action="{{ route('client.structures.store') }}" method="POST" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Nombre</label>
                        <input type="text" name="name" required class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Código</label>
                        <input type="text" name="code" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Tipo</label>
                        <select name="type" required class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                            @foreach ($types as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Padre (opcional)</label>
                        <select name="parent_id" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                            <option value="">— Raíz —</option>
                            @foreach ($parents as $parent)
                                <option value="{{ $parent->id }}">{{ $parent->full_path }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-500">
                        Crear estructura
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-client-layout>
