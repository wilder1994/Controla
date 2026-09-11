<x-client-layout title="Personas">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-white">Directorio de personas</h2>
                <p class="text-sm text-slate-400 mt-1">Personas asignadas a un nodo de la estructura (salón, apartamento, etc.).</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('client.members.export') }}" class="inline-flex rounded-lg bg-slate-700 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-600">
                    Exportar
                </a>
                <a href="{{ route('client.members.create') }}" class="inline-flex rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-500">
                    Nueva persona
                </a>
            </div>
        </div>

        <form method="GET" class="flex flex-wrap gap-3">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar nombre o documento"
                   class="rounded-lg bg-slate-900 border border-slate-700 px-3 py-2 text-sm text-white">
            <select name="structure_id" class="rounded-lg bg-slate-900 border border-slate-700 px-3 py-2 text-sm text-white">
                <option value="">Todas las unidades</option>
                @foreach ($structures as $structure)
                    <option value="{{ $structure->id }}" @selected(request('structure_id') == $structure->id)>{{ $structure->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm text-white hover:bg-slate-700">Filtrar</button>
        </form>

        <div class="rounded-xl border border-slate-800 overflow-hidden bg-slate-900">
            <table class="min-w-full divide-y divide-slate-800 text-sm">
                <thead class="bg-slate-950/60">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Persona</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Unidad</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Tipo</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Código</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse ($members as $member)
                        <tr class="hover:bg-slate-800/40">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @if($member->photo_path)
                                        <img src="{{ Storage::url($member->photo_path) }}" alt="" class="w-8 h-8 rounded-full object-cover ring-2 ring-slate-700">
                                    @else
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-teal-500 to-teal-700 flex items-center justify-center text-white text-xs font-bold ring-2 ring-slate-700">
                                            {{ strtoupper(substr($member->first_name, 0, 1)) }}{{ strtoupper(substr($member->last_name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div>
                                        <a href="{{ route('client.members.show', $member) }}" class="font-medium text-white hover:text-teal-300">{{ $member->full_name }}</a>
                                        <p class="text-xs text-slate-500">{{ $member->document_number }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-300">{{ $member->structure?->full_path }}</td>
                            <td class="px-4 py-3"><span class="inline-flex items-center rounded-full bg-slate-800 px-2 py-0.5 text-xs font-medium text-slate-300 ring-1 ring-slate-700">{{ $member->member_type->label() }}</span></td>
                            <td class="px-4 py-3 font-mono text-xs text-indigo-300">{{ $member->access_code }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('client.members.edit', $member) }}" class="text-xs text-slate-500 hover:text-teal-400 transition-colors">Editar</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">No hay personas en el censo.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $members->links() }}
    </div>
</x-client-layout>
