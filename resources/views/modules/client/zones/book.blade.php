<x-client-layout title="Reservar zona">
    <div class="space-y-6 max-w-3xl">
        <div>
            <a href="{{ route('client.zones.index') }}" class="text-sm text-slate-500 hover:text-white transition-colors">← Zonas comunes</a>
            <h2 class="text-2xl font-bold text-white mt-1">Reservar zona</h2>
        </div>

        @if($errors->any())
            <div class="rounded-lg bg-red-900/40 border border-red-700 text-red-200 px-4 py-3 text-sm">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="rounded-xl border border-slate-800 bg-slate-900 p-6">
            <form method="POST" action="{{ route('client.zones.store') }}" class="space-y-5" x-data="{ zoneId: '{{ old('common_zone_id', $selectedZone?->id ?? '') }}' }">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-slate-300">Zona</label>
                    <select name="common_zone_id" x-model="zoneId" required class="mt-1 block w-full rounded-lg bg-slate-950 border-slate-700 text-white focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Selecciona la zona...</option>
                        @foreach($zones as $zone)
                            <option value="{{ $zone->id }}" @selected(old('common_zone_id', $selectedZone?->id) == $zone->id)>
                                {{ $zone->name }} · {{ $zone->open_time?->format('H:i') }}–{{ $zone->close_time?->format('H:i') }} · Cap. {{ $zone->capacity }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-300">Fecha</label>
                        <input type="date" name="date" value="{{ old('date') }}" required class="mt-1 block w-full rounded-lg bg-slate-950 border-slate-700 text-white focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300">Desde</label>
                        <input type="time" name="start_time" value="{{ old('start_time') }}" required class="mt-1 block w-full rounded-lg bg-slate-950 border-slate-700 text-white focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300">Hasta</label>
                        <input type="time" name="end_time" value="{{ old('end_time') }}" required class="mt-1 block w-full rounded-lg bg-slate-950 border-slate-700 text-white focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-300">Número de personas</label>
                        <input type="number" name="people_count" value="{{ old('people_count', 1) }}" min="1" max="100" required class="mt-1 block w-full rounded-lg bg-slate-950 border-slate-700 text-white focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300">Unidad</label>
                        <select name="housing_unit_id" class="mt-1 block w-full rounded-lg bg-slate-950 border-slate-700 text-white focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">No especificada</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}" @selected(old('housing_unit_id') == $unit->id)>{{ $unit->full_label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300">Notas</label>
                    <textarea name="notes" rows="2" class="mt-1 block w-full rounded-lg bg-slate-950 border-slate-700 text-white focus:border-indigo-500 focus:ring-indigo-500" placeholder="Opcional"></textarea>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <a href="{{ route('client.zones.index') }}" class="inline-flex items-center px-4 py-2 bg-slate-800 border border-slate-700 rounded-lg font-semibold text-xs text-slate-300 hover:bg-slate-700 transition-colors">Cancelar</a>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 transition-colors shadow-sm">Confirmar reserva</button>
                </div>
            </form>
        </div>
    </div>
</x-client-layout>