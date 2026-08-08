<div
    x-data="{ openPanic: false, panicLat: '', panicLng: '', initPanic() { if (navigator.geolocation) { navigator.geolocation.getCurrentPosition((p) => { this.panicLat = p.coords.latitude.toFixed(7); this.panicLng = p.coords.longitude.toFixed(7); }, () => {}, { timeout: 5000 }); } } }"
    x-init="initPanic()"
    @open-panic.window="openPanic = true"
    x-cloak
>
    <div
        x-show="openPanic"
        @keydown.escape="openPanic = false"
        class="fixed inset-0 z-[60] overflow-y-auto"
        style="display: none;"
    >
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="openPanic" x-transition.opacity @click="openPanic = false" class="fixed inset-0 bg-black/60 backdrop-blur-sm"></div>
            <div x-show="openPanic" x-transition:enter="transition-transform duration-300 ease-out" x-transition:enter-start="scale-95 opacity-0" x-transition:enter-end="scale-100 opacity-100" class="relative bg-slate-900 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-800" @click.stop>
                <div class="bg-gradient-to-r from-red-700 to-rose-800 px-6 py-5 text-center">
                    <div class="w-16 h-16 mx-auto bg-white/10 rounded-full flex items-center justify-center mb-3">
                        <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    </div>
                    <p class="text-lg font-bold text-white">¿Generar alerta de pánico?</p>
                    <p class="text-sm text-red-200 mt-1">Esta acción notificará al personal de seguridad.</p>
                </div>
                <form method="POST" action="{{ route('access.guard_logs.panic') }}" class="p-5 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">Ubicación</label>
                        <select name="location_id" required class="w-full rounded-lg bg-slate-950 border-slate-700 text-white focus:border-red-500 focus:ring-red-500">
                            @foreach(\App\Models\Location::where('is_active', true)->get() as $loc)
                            <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">Descripción de la emergencia</label>
                        <textarea name="description" rows="2" required class="w-full rounded-lg bg-slate-950 border-slate-700 text-white focus:border-red-500 focus:ring-red-500" placeholder="Describa la situación..."></textarea>
                    </div>
                    <input type="hidden" name="latitude" x-bind:value="panicLat">
                    <input type="hidden" name="longitude" x-bind:value="panicLng">
                    <div class="flex gap-3">
                        <button type="button" @click="openPanic = false" class="flex-1 px-4 py-2 text-sm font-medium text-slate-300 bg-slate-800 border border-slate-700 rounded-lg hover:bg-slate-700">Cancelar</button>
                        <button type="submit" class="flex-1 px-4 py-2 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg inline-flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                            Activar Pánico
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>