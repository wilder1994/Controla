<x-access-layout>
    <div class="-mt-6 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 pt-6 pb-8 bg-gradient-to-r from-slate-800 to-indigo-900 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-indigo-300">Vigilancia</p>
                <h2 class="text-xl font-bold text-white">Nueva Minuta</h2>
            </div>
            <a href="{{ route('access.guard_logs.index') }}" class="text-sm text-indigo-300 hover:text-white transition-colors">← Volver</a>
        </div>
    </div>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-800">
                <h3 class="text-lg font-semibold text-white">Registrar Novedad</h3>
                <p class="text-sm text-slate-500 mt-0.5">Complete los campos para registrar una nueva minuta de vigilancia</p>
            </div>
            <div class="px-6 py-5" x-data="geoCapture()">
                <form method="POST" action="{{ route('access.guard_logs.store') }}">
                    @csrf

                    @if($errors->any())
                        <div class="mb-5 rounded-lg bg-red-900/40 border border-red-700 text-red-200 px-4 py-3 text-sm">
                            @foreach($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-slate-300">Ubicación</label>
                            <select name="location_id" required class="mt-1 block w-full rounded-lg bg-slate-950 border-slate-700 text-white focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($locations as $loc)
                                <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300">Fecha/Hora</label>
                            <input type="datetime-local" name="log_time" value="{{ old('log_time', date('Y-m-d\TH:i')) }}" class="mt-1 block w-full rounded-lg bg-slate-950 border-slate-700 text-white focus:border-indigo-500 focus:ring-indigo-500" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300">Tipo</label>
                            <select name="type" class="mt-1 block w-full rounded-lg bg-slate-950 border-slate-700 text-white focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="general">📋 General</option>
                                <option value="novedad">🔶 Novedad</option>
                                <option value="turno">🔄 Cambio de Turno</option>
                                <option value="incidente">🚨 Incidente</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300">Turno</label>
                            <select name="shift_type" class="mt-1 block w-full rounded-lg bg-slate-950 border-slate-700 text-white focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="diurno">☀️ Diurno</option>
                                <option value="nocturno">🌙 Nocturno</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-5 bg-slate-950 rounded-lg p-4 border border-slate-700">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <span class="text-sm font-medium text-slate-300">Geolocalización</span>
                            </div>
                            <button @click="capture()" type="button" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-indigo-300 bg-indigo-900/40 rounded-lg hover:bg-indigo-800/60 transition-colors" x-text="captured ? 'Capturada' : 'Capturar ubicación'"></button>
                        </div>
                        <div class="mt-2 text-xs text-slate-500" x-show="!captured && !error && !loading">
                            La ubicación se usará para verificar la presencia del guardia en el sitio.
                        </div>
                        <div class="mt-2 text-xs text-emerald-400" x-show="captured" x-cloak>
                            <span class="font-medium">✓ Ubicación capturada:</span>
                            <span x-text="`${lat}, ${lng}`"></span>
                        </div>
                        <div class="mt-2 text-xs text-red-400" x-show="error" x-cloak x-text="error"></div>
                        <div class="mt-2 text-xs text-slate-500" x-show="loading" x-cloak>Obteniendo ubicación...</div>
                        <input type="hidden" name="latitude" x-model="lat">
                        <input type="hidden" name="longitude" x-model="lng">
                    </div>

                    <div class="mt-5">
                        <label class="block text-sm font-medium text-slate-300">Descripción</label>
                        <textarea name="description" rows="4" class="mt-1 block w-full rounded-lg bg-slate-950 border-slate-700 text-white focus:border-indigo-500 focus:ring-indigo-500" placeholder="Describa la novedad, incidente o novedad ocurrida durante el turno..." required>{{ old('description') }}</textarea>
                    </div>

                    <div class="mt-5" x-data="{ requiresSupervisor: {{ in_array(old('type', 'general'), ['incidente', 'novedad'], true) ? 'true' : 'false' }} }">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" x-model="requiresSupervisor" name="requires_supervisor" value="1" class="mt-0.5 rounded bg-slate-950 border-indigo-600 text-indigo-500 focus:ring-indigo-500">
                            <div>
                                <p class="text-sm font-medium text-indigo-200">Requiere firma de supervisor</p>
                                <p class="text-xs text-slate-400">Para novedades e incidentes el supervisor debe validar con su código único.</p>
                            </div>
                        </label>

                        <div x-show="requiresSupervisor" x-cloak class="mt-3 p-4 bg-indigo-950/40 rounded-lg border border-indigo-700/60">
                            <label class="block text-sm font-medium text-slate-300">Código del supervisor</label>
                            <input type="text" name="supervision_code" x-model="supervisorCode" autocomplete="off" placeholder="Ingrese el código único del supervisor..." class="mt-1 block w-full max-w-xs rounded-lg bg-slate-950 border-slate-700 text-white focus:border-indigo-500 focus:ring-indigo-500">
                            <p class="mt-2 text-xs text-slate-500">El sistema validará el código contra los códigos de supervisión activos y registrará quién firmó la minuta.</p>
                        </div>
                    </div>

                    <div class="mt-5 bg-amber-900/30 rounded-lg p-4 border border-amber-700">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="signed" value="1" class="mt-0.5 rounded bg-slate-950 border-amber-600 text-amber-500 focus:ring-amber-500" required>
                            <div>
                                <p class="text-sm font-medium text-amber-200">Confirmo que la información registrada es verídica</p>
                                <p class="text-xs text-amber-400">Firma digital — doble factor de autenticación. Al marcar, el sistema registrará su identidad y hora de confirmación.</p>
                            </div>
                        </label>
                    </div>

                    <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-slate-800">
                        <a href="{{ route('access.guard_logs.index') }}" class="inline-flex items-center px-4 py-2 bg-slate-800 border border-slate-700 rounded-lg font-semibold text-xs text-slate-300 hover:bg-slate-700 transition-colors">Cancelar</a>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-amber-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-700 transition-colors shadow-sm">
                            <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Guardar Minuta
                        </button>
                    </div>
                </form>
            </div>

@push('scripts')
<script>
    function geoCapture() {
        return {
            lat: '{{ old('latitude') }}',
            lng: '{{ old('longitude') }}',
            captured: !!'{{ old('latitude') }}',
            loading: false,
            error: '',
            capture() {
                if (!navigator.geolocation) {
                    this.error = 'Geolocalización no disponible en este navegador.';
                    return;
                }
                this.loading = true;
                this.error = '';
                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        this.lat = pos.coords.latitude.toFixed(7);
                        this.lng = pos.coords.longitude.toFixed(7);
                        this.captured = true;
                        this.loading = false;
                    },
                    (err) => {
                        this.loading = false;
                        switch(err.code) {
                            case err.PERMISSION_DENIED:
                                this.error = 'Permiso denegado. Active la ubicación en su navegador.';
                                break;
                            case err.POSITION_UNAVAILABLE:
                                this.error = 'Ubicación no disponible.';
                                break;
                            case err.TIMEOUT:
                                this.error = 'Tiempo de espera agotado.';
                                break;
                        }
                    },
                    { enableHighAccuracy: true, timeout: 10000 }
                );
            }
        }
    }
</script>
@endpush
        </div>
    </div>
</x-access-layout>