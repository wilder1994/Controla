<x-access-layout title="Visitantes">
    <div class="flex gap-2 mb-4">
        <a href="{{ route('access.visitors.index') }}" class="px-3 py-1.5 rounded-lg text-sm {{ $tab === 'personas' ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-300' }}">Personas</a>
        <a href="{{ route('access.visitors.index', ['tab' => 'vehiculos']) }}" class="px-3 py-1.5 rounded-lg text-sm {{ $tab === 'vehiculos' ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-300' }}">Vehículos</a>
        <a href="{{ route('access.visitors.create') }}" class="ml-auto px-3 py-1.5 rounded-lg text-sm bg-indigo-700 text-white">Nuevo visitante</a>
    </div>

    @if($tab === 'vehiculos')
        <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
            <table class="min-w-full divide-y divide-slate-800 text-sm">
                <thead class="bg-slate-950/60">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs uppercase text-slate-500">Placa</th>
                        <th class="px-6 py-3 text-left text-xs uppercase text-slate-500">Visitante</th>
                        <th class="px-6 py-3 text-left text-xs uppercase text-slate-500">Marca</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($vehicles as $vehicle)
                        <tr>
                            <td class="px-6 py-4 font-mono text-white">{{ $vehicle->plate }}</td>
                            <td class="px-6 py-4 text-slate-400">{{ $vehicle->visitor?->full_name ?? '—' }}</td>
                            <td class="px-6 py-4 text-slate-400">{{ $vehicle->brand }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-6 py-10 text-center text-slate-500">Sin vehículos de visita.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $vehicles->links() }}</div>
    @else
        <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
            <table class="min-w-full divide-y divide-slate-800">
                <thead class="bg-slate-950/60">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs uppercase text-slate-500">Documento</th>
                        <th class="px-6 py-3 text-left text-xs uppercase text-slate-500">Nombre</th>
                        <th class="px-6 py-3 text-left text-xs uppercase text-slate-500">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($visitors as $visitor)
                        <tr>
                            <td class="px-6 py-4 text-white text-sm">{{ $visitor->displayedDocument() }}</td>
                            <td class="px-6 py-4 text-slate-400 text-sm">{{ $visitor->full_name }}</td>
                            <td class="px-6 py-4 text-sm space-x-2">
                                <a href="{{ route('access.visitors.show', $visitor) }}" class="text-indigo-400">Ver</a>
                                <a href="{{ route('access.visitors.edit', $visitor) }}" class="text-yellow-500">Editar</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-6 py-10 text-center text-slate-500">Sin visitantes.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $visitors->links() }}</div>
    @endif
</x-access-layout>
