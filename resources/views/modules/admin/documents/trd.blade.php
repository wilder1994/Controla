<x-admin-layout title="TRD">
    <div class="flex flex-col flex-1 min-h-0 gap-3">
        <div class="flex items-center justify-between gap-3 shrink-0">
            <div>
                <p class="text-xs text-slate-500">Documentos · Tabla de retención documental</p>
                <p class="text-xs text-slate-500 mt-1">Series comerciales y operativas con plazos y disposición final.</p>
            </div>
            <x-ui.button variant="secondary" :href="route('admin.documents.index')" size="sm">← Documentos</x-ui.button>
        </div>

        <div class="flex-1 min-h-0 rounded-lg border border-slate-800 bg-slate-900/80 overflow-hidden flex flex-col">
            <div class="flex-1 overflow-y-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-950/60 text-xs uppercase tracking-wide text-slate-500 sticky top-0">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium">Serie</th>
                            <th class="px-4 py-2 text-left font-medium">Subserie</th>
                            <th class="px-4 py-2 text-left font-medium">Retención</th>
                            <th class="px-4 py-2 text-left font-medium">Días</th>
                            <th class="px-4 py-2 text-left font-medium">Disposición</th>
                            <th class="px-4 py-2 text-left font-medium">Base legal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @forelse ($series as $row)
                            <tr class="hover:bg-slate-800/30">
                                <td class="px-4 py-2.5 text-slate-200">{{ $row->series }}</td>
                                <td class="px-4 py-2.5 text-slate-300">{{ $row->subseries }}</td>
                                <td class="px-4 py-2.5 text-slate-400">{{ $row->retention_label }}</td>
                                <td class="px-4 py-2.5 text-slate-400 tabular-nums">{{ $row->retention_days ?? '—' }}</td>
                                <td class="px-4 py-2.5 text-slate-300">{{ $row->disposition }}</td>
                                <td class="px-4 py-2.5 text-xs text-slate-500">{{ $row->legal_basis }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-slate-500">Sin series TRD. Ejecuta el seeder de plataforma.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin-layout>
