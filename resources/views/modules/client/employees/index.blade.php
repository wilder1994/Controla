<x-client-layout title="Empleados">
    <div class="space-y-4">
        <form method="GET" class="flex flex-wrap gap-2">
            <input type="search" name="q" value="{{ $q }}" placeholder="Nombre o documento"
                   class="h-9 rounded-lg border border-slate-700 bg-slate-950 px-3 text-sm text-white">
            <button type="submit" class="h-9 rounded-lg border border-slate-700 px-3 text-sm text-slate-200">Buscar</button>
        </form>
        <div class="rounded-xl border border-slate-800 overflow-hidden bg-slate-900">
            <table class="min-w-full divide-y divide-slate-800 text-sm">
                <thead class="bg-slate-950/60">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Empleado</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Documento</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse ($employees as $employee)
                        <tr>
                            <td class="px-4 py-3 text-white">{{ $employee->fullName() }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-400">{{ $employee->document_number }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-4 py-10 text-center text-slate-500">No hay empleados en los puestos de este alcance.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $employees->links() }}
    </div>
</x-client-layout>
