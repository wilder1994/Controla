<x-client-layout title="Nuevo vehículo">
    <form action="{{ route('client.vehicles.store') }}" method="POST" class="max-w-2xl space-y-4 rounded-xl border border-slate-800 bg-slate-900 p-6">
        @csrf
        <div>
            <label class="block text-xs text-slate-400 mb-1">Unidad</label>
            <select name="structure_id" required class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                @foreach ($structures as $structure)
                    <option value="{{ $structure->id }}">{{ $structure->full_path }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-slate-400 mb-1">Placa</label>
            <input type="text" name="plate" required class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white uppercase">
        </div>
        <div class="grid sm:grid-cols-3 gap-4">
            <input type="text" name="brand" placeholder="Marca" class="rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
            <input type="text" name="model" placeholder="Modelo" class="rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
            <input type="text" name="color" placeholder="Color" class="rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
        </div>
        <div class="grid sm:grid-cols-2 gap-4">
            <input type="date" name="soat_expires_at" class="rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
            <input type="date" name="license_expires_at" class="rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
        </div>
        <input type="text" name="assigned_parking_spot" placeholder="Celda parqueadero" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
        <label class="flex items-center gap-2 text-sm text-slate-300">
            <input type="checkbox" name="is_visitor_vehicle" value="1" class="rounded border-slate-600 bg-slate-950 text-teal-600">
            Vehículo visitante
        </label>
        <button type="submit" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-500">Guardar</button>
    </form>
</x-client-layout>
