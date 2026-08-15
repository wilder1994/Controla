<x-access-layout>
    <div class="-mt-6 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 pt-6 pb-8 bg-gradient-to-r from-slate-800 to-indigo-900 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-indigo-300">Control de Acceso</p>
                <h2 class="text-xl font-bold text-white">Abrir Turno</h2>
            </div>
            <a href="{{ route('access.turnos.index') }}" class="text-sm text-indigo-300 hover:text-white transition-colors">← Mis turnos</a>
        </div>
    </div>

    <div class="max-w-xl">
        <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-800">
                <h3 class="text-sm font-semibold text-slate-200">Iniciar turno de portería</h3>
                <p class="text-xs text-slate-500 mt-1">Al iniciar tu turno quedan habilitadas las operaciones de ingreso, salida, minutas y vehículos.</p>
            </div>
            <form method="POST" action="{{ route('access.turnos.store') }}" class="p-6 space-y-5">
                @csrf

                @if($errors->any())
                    <div class="rounded-lg bg-red-900/40 border border-red-700 text-red-200 px-4 py-3 text-sm">
                        @foreach($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-slate-300">Ubicación / Portería</label>
                    <select name="location_id" class="mt-1 block w-full rounded-lg bg-slate-950 border-slate-700 text-white focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">No especificada</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300">Nota de apertura</label>
                    <textarea name="start_notes" rows="3" class="mt-1 block w-full rounded-lg bg-slate-950 border-slate-700 text-white focus:border-indigo-500 focus:ring-indigo-500" placeholder="Estado del turno, personal relevante, pendientes..."></textarea>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <a href="{{ route('access.operations') }}" class="inline-flex items-center px-4 py-2 bg-slate-800 border border-slate-700 rounded-lg font-semibold text-xs text-slate-300 hover:bg-slate-700 transition-colors">Cancelar</a>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-500 transition-colors shadow-sm">
                        <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        Iniciar Turno
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-access-layout>