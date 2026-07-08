<x-client-layout title="Vehículos">
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h2 class="text-2xl font-bold text-white">Directorio vehicular</h2>
            <a href="{{ route('client.vehicles.create') }}" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-500">Nuevo vehículo</a>
        </div>
        <form method="GET" class="flex flex-wrap gap-3">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Placa" class="rounded-lg bg-slate-900 border border-slate-700 px-3 py-2 text-sm text-white">
            <select name="structure_id" class="rounded-lg bg-slate-900 border border-slate-700 px-3 py-2 text-sm text-white">
                <option value="">Todas las unidades</option>
                @foreach ($structures as $structure)
                    <option value="{{ $structure->id }}" @selected(request('structure_id') == $structure->id)>{{ $structure->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm text-white">Filtrar</button>
        </form>
        <div class="rounded-xl border border-slate-800 overflow-hidden bg-slate-900">
            <table class="min-w-full text-sm divide-y divide-slate-800">
                <thead class="bg-slate-950/60">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs uppercase text-slate-500">Placa</th>
                        <th class="px-4 py-3 text-left text-xs uppercase text-slate-500">Vehículo</th>
                        <th class="px-4 py-3 text-left text-xs uppercase text-slate-500">Unidad</th>
                        <th class="px-4 py-3 text-left text-xs uppercase text-slate-500">Parqueadero</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse ($vehicles as $vehicle)
                        <tr>
                            <td class="px-4 py-3 font-mono text-teal-300">{{ $vehicle->plate }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ trim("{$vehicle->brand} {$vehicle->model}") }}</td>
                            <td class="px-4 py-3 text-slate-400">{{ $vehicle->structure?->name }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $vehicle->assigned_parking_spot ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">Sin vehículos en censo.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $vehicles->links() }}
    </div>
</x-client-layout>
