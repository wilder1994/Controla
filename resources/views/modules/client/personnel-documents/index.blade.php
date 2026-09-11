<x-client-layout title="Documentos">
    @php
        $hasPeople = $employees->isNotEmpty();
        $searching = filled($q);
    @endphp

    <div class="space-y-4">
        <div>
            <p class="text-xs uppercase tracking-wider text-slate-500">Personal asignado</p>
            <h2 class="text-2xl font-semibold text-white">Carpetas de empleados</h2>
            <p class="text-sm text-slate-400 mt-1">Solo empleados con puesto en este cliente. Consulta; la empresa indexa.</p>
        </div>

        <form method="get" class="flex flex-wrap items-end gap-2">
            <div class="min-w-[16rem] flex-1">
                <x-ui.input name="q" :value="$q" placeholder="Nombre o cédula" />
            </div>
            <x-ui.button type="submit" variant="secondary" size="sm">Buscar</x-ui.button>
        </form>

        @if (! $hasPeople && ! $searching)
            <div class="rounded-xl border border-slate-800 bg-slate-900/60 px-6 py-10 text-center">
                <p class="text-white font-medium">No hay empleados asignados a un puesto</p>
                <p class="text-sm text-slate-400 mt-1">Cuando la empresa asigne personal a un puesto de este cliente, verá sus carpetas aquí.</p>
            </div>
        @else
            <div class="rounded-lg border border-slate-800 overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-900/80 text-slate-400 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">Cédula</th>
                            <th class="px-4 py-3 text-left">Nombre</th>
                            <th class="px-4 py-3 text-left">Carpetas</th>
                            <th class="px-4 py-3 text-left">Documentos</th>
                            <th class="px-4 py-3 text-left">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @forelse ($employees as $employee)
                            <tr class="bg-slate-950/40">
                                <td class="px-4 py-3 text-slate-300">
                                    <span class="text-slate-500">{{ $employee->document_type }}</span>
                                    {{ $employee->document_number }}
                                </td>
                                <td class="px-4 py-3 text-white">{{ $employee->fullName() }}</td>
                                <td class="px-4 py-3 text-slate-300">{{ (int) $employee->folders_count }}/{{ $folderTotal }}</td>
                                <td class="px-4 py-3 text-slate-300">{{ (int) $employee->documents_count }}</td>
                                <td class="px-4 py-3">
                                    <a class="text-teal-300 hover:text-white" href="{{ route('client.personnel-documents.folder', $employee) }}">Ver carpeta</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-slate-400">No hay coincidencias para esa búsqueda.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div>{{ $employees->links() }}</div>
        @endif
    </div>
</x-client-layout>
