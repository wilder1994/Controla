<x-access-layout title="Instalaciones">
    <div class="rounded-xl border border-slate-800 overflow-hidden bg-slate-900">
        <table class="min-w-full text-sm divide-y divide-slate-800">
            <thead class="bg-slate-950/60">
                <tr>
                    <th class="px-4 py-3 text-left text-xs uppercase text-slate-500">Instalación</th>
                    <th class="px-4 py-3 text-left text-xs uppercase text-slate-500">Nodos</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @forelse ($sites as $site)
                    <tr>
                        <td class="px-4 py-3 text-white">{{ $site->name }}</td>
                        <td class="px-4 py-3 text-slate-400">{{ $site->structures_count }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="px-4 py-10 text-center text-slate-500">Sin instalaciones.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-access-layout>
