<x-access-layout title="Resumen">
    <div class="space-y-6">
        <div class="flex flex-wrap gap-2 justify-end">
            <a href="{{ route('access.logs.entry') }}" class="inline-flex items-center px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold rounded-lg">Ingreso</a>
            <a href="{{ route('access.logs.exit.page') }}" class="inline-flex items-center px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold rounded-lg">Salida</a>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-slate-900 rounded-xl border border-slate-800 p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Dentro</p>
                <p class="mt-1 text-2xl font-bold text-white">{{ $activeEntries }}</p>
            </div>
            <div class="bg-slate-900 rounded-xl border border-slate-800 p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Ingresos hoy</p>
                <p class="mt-1 text-2xl font-bold text-white">{{ $todayEntries }}</p>
            </div>
            <div class="bg-slate-900 rounded-xl border border-slate-800 p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Correspondencia</p>
                <p class="mt-1 text-2xl font-bold text-amber-400">{{ $pendingCorrespondence }}</p>
            </div>
            <div class="bg-slate-900 rounded-xl border border-slate-800 p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Autorizaciones hoy</p>
                <p class="mt-1 text-2xl font-bold text-indigo-300">{{ $pendingAuthorizations }}</p>
            </div>
        </div>

        <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-white">Personas dentro</h3>
                <a href="{{ route('access.logs.index') }}" class="text-xs text-indigo-400 hover:text-indigo-300">Ingreso y salida →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm divide-y divide-slate-800">
                    <thead class="bg-slate-950/60">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs uppercase text-slate-500">Persona</th>
                            <th class="px-4 py-3 text-left text-xs uppercase text-slate-500">Tipo</th>
                            <th class="px-4 py-3 text-left text-xs uppercase text-slate-500">Nodo / puerta</th>
                            <th class="px-4 py-3 text-left text-xs uppercase text-slate-500">Ingreso</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @forelse($peopleInside as $log)
                            <tr class="{{ $log->alert_long_stay ? 'bg-red-950/30' : '' }}">
                                <td class="px-4 py-3 text-white">{{ $log->person_name }}</td>
                                <td class="px-4 py-3 text-slate-400">{{ $log->person_type }}</td>
                                <td class="px-4 py-3 text-slate-400">{{ $log->destination }} · {{ $log->location?->name }}</td>
                                <td class="px-4 py-3 text-slate-400">{{ $log->entry_time->format('H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-10 text-center text-slate-500">Nadie dentro en esta puerta.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-access-layout>
