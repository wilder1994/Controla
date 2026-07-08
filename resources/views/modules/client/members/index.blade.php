<x-client-layout title="Personas">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-white">Directorio de personas</h2>
                <p class="text-sm text-slate-400 mt-1">Censo global con filtros por unidad — §1.2.2.</p>
            </div>
            <a href="{{ route('client.members.create') }}" class="inline-flex rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-500">
                Nueva persona
            </a>
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
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Código acceso</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse ($members as $member)
                        <tr class="hover:bg-slate-800/40">
                            <td class="px-4 py-3">
                                <a href="{{ route('client.members.show', $member) }}" class="font-medium text-white hover:text-teal-300">{{ $member->full_name }}</a>
                                <p class="text-xs text-slate-500">{{ $member->document_number }}</p>
                            </td>
                            <td class="px-4 py-3 text-slate-300">{{ $member->structure?->name }}</td>
                            <td class="px-4 py-3 text-slate-400">{{ $member->member_type->label() }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-indigo-300">{{ $member->access_code }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-10 text-center text-slate-500">No hay personas en el censo.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $members->links() }}
    </div>
</x-client-layout>
