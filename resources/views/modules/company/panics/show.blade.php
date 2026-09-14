<x-company-layout title="{{ $attention->folio() }}">
    @php
        $alert = $attention->alert;
        $lat = $alert?->latitude;
        $lng = $alert?->longitude;
        $canEdit = $attention->isOpen() && $attention->isAttendedBy(auth()->user());
    @endphp

    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div>
            <p class="text-xs uppercase tracking-wide text-slate-500">Ficha de pánico</p>
            <h2 class="text-xl font-semibold text-white">{{ $attention->folio() }}</h2>
            <p class="text-sm {{ $attention->isOpen() ? 'text-amber-300' : 'text-emerald-300' }}">{{ $attention->status->label() }}</p>
        </div>
        <a href="{{ route('company.panics.print', $attention) }}" target="_blank"
           class="inline-flex h-9 items-center px-3 rounded-lg bg-indigo-600 text-sm font-medium text-white hover:bg-indigo-500">
            Descargar / imprimir
        </a>
    </div>

    <div class="grid gap-3 xl:grid-cols-2">
        <section class="rounded-xl border border-slate-800 bg-slate-900/60 p-4 space-y-2 text-sm">
            <p class="text-[10px] uppercase tracking-wide text-slate-500">Alerta</p>
            <p class="text-slate-200">{{ $alert?->body }}</p>
            <p><span class="text-slate-500">Activó:</span> {{ $alert?->actor?->name ?? '—' }}</p>
            <p><span class="text-slate-500">Cliente:</span> {{ $alert?->client?->name ?? '—' }}</p>
            <p><span class="text-slate-500">Instalación:</span> {{ $alert?->installation?->name ?? '—' }}</p>
            <p><span class="text-slate-500">Atiende:</span> {{ $attention->attendee?->name }}</p>
            <p><span class="text-slate-500">Registrado:</span> {{ $alert?->created_at?->format('d/m/Y H:i') }}</p>
            @if ($lat !== null && $lng !== null)
                <p><span class="text-slate-500">GPS:</span> {{ number_format((float) $lat, 6) }}, {{ number_format((float) $lng, 6) }}</p>
            @endif
        </section>

        <section class="rounded-xl border border-slate-800 bg-slate-900/60 overflow-hidden min-h-64">
            @if ($mapsKey && $lat !== null && $lng !== null)
                <div id="panic-map" class="h-64 w-full"></div>
                @push('scripts')
                    <script>
                        window.initPanicFichaMap = function () {
                            const el = document.getElementById('panic-map');
                            if (!el || !window.google?.maps) return;
                            const pos = { lat: {{ (float) $lat }}, lng: {{ (float) $lng }} };
                            const map = new google.maps.Map(el, { center: pos, zoom: 16, mapTypeId: 'hybrid', streetViewControl: false });
                            new google.maps.Marker({ position: pos, map, title: 'Pánico' });
                        };
                    </script>
                    <script src="https://maps.googleapis.com/maps/api/js?key={{ $mapsKey }}&callback=initPanicFichaMap" async defer></script>
                @endpush
            @else
                <p class="p-4 text-sm text-slate-500">Sin coordenadas o sin clave de Maps.</p>
            @endif
        </section>
    </div>

    <form method="POST" action="{{ route('company.panics.update', $attention) }}" class="mt-4 rounded-xl border border-slate-800 bg-slate-900/60 p-4 space-y-3">
        @csrf
        @method('PUT')
        <label for="observations" class="block text-sm text-slate-300">Observaciones de la atención</label>
        <textarea id="observations" name="observations" rows="5" maxlength="4000"
                  @disabled(! $canEdit)
                  class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
                  placeholder="Qué se hizo en la atención">{{ old('observations', $attention->observations) }}</textarea>
        <x-ui.field-error :messages="$errors->get('observations')" />
        @if ($canEdit)
            <div class="flex flex-wrap gap-2">
                <button type="submit" class="h-9 px-3 rounded-lg border border-slate-700 text-sm text-slate-200">Guardar</button>
                <button type="submit" name="close" value="1" class="h-9 px-3 rounded-lg bg-emerald-700 text-sm text-white">Cerrar ficha</button>
            </div>
        @elseif ($attention->isOpen())
            <p class="text-xs text-slate-500">Solo {{ $attention->attendee?->name }} puede editar o cerrar esta ficha.</p>
        @endif
    </form>
</x-company-layout>
