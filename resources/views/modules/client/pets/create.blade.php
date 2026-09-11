<x-client-layout title="Nueva mascota">
    <div class="max-w-2xl">
        <form action="{{ route('client.pets.store') }}" method="POST" class="space-y-4 rounded-xl border border-slate-800 bg-slate-900 p-6">
            @csrf
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs text-slate-400 mb-1">Nombre</label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                </div>
                <div>
                    <label class="block text-xs text-slate-400 mb-1">Especie</label>
                    <select name="species" required class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                        @foreach ($species as $value => $label)
                            <option value="{{ $value }}" @selected(old('species') == $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <x-client.census-node-picker
                :installations="$installations"
                :node-options="$nodeOptions"
                :installation-id="$installationId"
                :structure-id="$structureId"
            />
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs text-slate-400 mb-1">Raza</label>
                    <input type="text" name="breed" value="{{ old('breed') }}" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                </div>
                <div class="flex items-end pb-2">
                    <label class="flex items-center gap-2 text-sm text-slate-300">
                        <input type="checkbox" name="is_potentially_dangerous" value="1" class="rounded border-slate-600 bg-slate-950 text-red-600">
                        Potencialmente peligroso
                    </label>
                </div>
            </div>
            <button type="submit" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-500">Registrar mascota</button>
        </form>
    </div>
</x-client-layout>
