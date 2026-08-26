@php
    $liveJson = json_encode($map['live'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    $historyJson = json_encode($map['history'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    $googleMapsJson = json_encode($map['google_maps'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
@endphp

<x-company-layout title="Supervisión">
    <div class="space-y-4" x-data="{ tab: 'live', replayIndex: 0, replayPath: {{ json_encode($map['history'][0]['path'] ?? []) }} }">
        <form method="GET" action="{{ route('company.supervision.index') }}" class="rounded-lg border border-slate-800 bg-slate-900/60 p-3 flex flex-col sm:flex-row sm:items-end gap-3">
            <div>
                <label for="from" class="text-xs text-slate-500">Desde</label>
                <input type="date" id="from" name="from" value="{{ $map['from'] }}"
                       class="mt-1 w-full h-9 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white">
            </div>
            <div>
                <label for="to" class="text-xs text-slate-500">Hasta</label>
                <input type="date" id="to" name="to" value="{{ $map['to'] }}"
                       class="mt-1 w-full h-9 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white">
            </div>
            <x-ui.button type="submit" size="sm" variant="secondary">Filtrar historial</x-ui.button>
            <p class="text-xs text-slate-500 sm:ml-auto">
                {{ count($map['live']) }} en vivo · {{ count($map['history']) }} turnos en rango
            </p>
        </form>

        <div class="flex gap-2">
            <button type="button" class="admin-header-tab" :class="tab === 'live' && 'is-active'" @click="tab = 'live'">En vivo</button>
            <button type="button" class="admin-header-tab" :class="tab === 'history' && 'is-active'" @click="tab = 'history'">Historial / replay</button>
        </div>

        <div id="supervision-map" class="w-full h-[420px] rounded-lg border border-slate-800 bg-slate-950/60 overflow-hidden relative">
            <div id="supervision-map-fallback" class="absolute inset-0 flex items-center justify-center text-center p-6 text-sm text-slate-500 hidden">
                <div>
                    <p class="text-slate-300 font-medium mb-1">Mapa no disponible</p>
                    <p>Configura <code class="text-indigo-300">GOOGLE_MAPS_API_KEY</code>.</p>
                </div>
            </div>
        </div>

        <section class="rounded-lg border border-slate-800 bg-slate-900/80 p-4" x-show="tab === 'live'">
            <h3 class="text-sm font-semibold text-white">Supervisores en turno</h3>
            <ul class="mt-3 space-y-2">
                @forelse ($map['live'] as $row)
                    <li class="text-sm text-slate-300">
                        {{ $row['user'] ?? 'Supervisor' }}
                        @if ($row['lat'])
                            · {{ $row['lat'] }}, {{ $row['lng'] }}
                        @else
                            · sin GPS aún
                        @endif
                    </li>
                @empty
                    <li class="text-sm text-slate-500">Nadie en turno ahora.</li>
                @endforelse
            </ul>
        </section>

        <section class="rounded-lg border border-slate-800 bg-slate-900/80 p-4" x-show="tab === 'history'" x-cloak>
            <h3 class="text-sm font-semibold text-white">Rutas del periodo</h3>
            <div class="mt-3 flex flex-wrap items-center gap-3" x-show="replayPath.length > 1">
                <label class="text-xs text-slate-500">Replay</label>
                <input type="range" min="0" :max="Math.max(0, replayPath.length - 1)" x-model.number="replayIndex"
                       class="flex-1 accent-amber-400" @input="window.supervisionReplayAt?.(replayIndex)">
                <x-ui.button type="button" size="sm" variant="secondary" @click="window.supervisionReplayPlay?.()">Reproducir</x-ui.button>
            </div>
            <ul class="mt-3 space-y-2 max-h-64 overflow-y-auto">
                @forelse ($map['history'] as $row)
                    <li class="text-sm text-slate-300">
                        {{ $row['user'] ?? 'Supervisor' }} · {{ $row['status'] }}
                        · {{ \Illuminate\Support\Carbon::parse($row['started_at'])->format('d/m H:i') }}
                        @if ($row['km_traveled'])
                            · {{ $row['km_traveled'] }} km
                        @endif
                        · {{ count($row['path']) }} puntos
                    </li>
                @empty
                    <li class="text-sm text-slate-500">Sin turnos en el rango.</li>
                @endforelse
            </ul>
        </section>
    </div>

    @push('scripts')
        <script>
            (function () {
                const live = {!! $liveJson !!};
                const history = {!! $historyJson !!};
                const googleMaps = {!! $googleMapsJson !!};
                const mapEl = document.getElementById('supervision-map');
                const fallback = document.getElementById('supervision-map-fallback');

                window.initSupervisionMap = function () {
                    if (!mapEl || !window.google?.maps) {
                        fallback?.classList.remove('hidden');
                        return;
                    }
                    const center = googleMaps.center || { lat: 4.57, lng: -74.29 };
                    const map = new google.maps.Map(mapEl, {
                        center,
                        zoom: googleMaps.zoom || 6,
                        mapTypeId: google.maps.MapTypeId.ROADMAP,
                    });
                    const bounds = new google.maps.LatLngBounds();
                    let hasPoint = false;

                    live.forEach((row) => {
                        if (row.lat == null || row.lng == null) return;
                        const pos = { lat: row.lat, lng: row.lng };
                        new google.maps.Marker({ map, position: pos, title: row.user || 'Supervisor' });
                        bounds.extend(pos);
                        hasPoint = true;
                    });

                    history.forEach((row) => {
                        if (!row.path?.length) return;
                        const path = row.path.map((p) => ({ lat: p.lat, lng: p.lng }));
                        new google.maps.Polyline({
                            map,
                            path,
                            strokeColor: '#f59e0b',
                            strokeOpacity: 0.8,
                            strokeWeight: 3,
                        });
                        path.forEach((p) => { bounds.extend(p); hasPoint = true; });
                    });

                    let replayMarker = null;
                    let replayTimer = null;
                    const firstPath = (history.find((row) => row.path?.length > 1) || {}).path || [];

                    window.supervisionReplayAt = function (index) {
                        if (!firstPath.length || !replayMarker) return;
                        const point = firstPath[index] || firstPath[0];
                        replayMarker.setPosition({ lat: point.lat, lng: point.lng });
                    };

                    window.supervisionReplayPlay = function () {
                        if (!firstPath.length) return;
                        let i = 0;
                        clearInterval(replayTimer);
                        replayTimer = setInterval(() => {
                            window.supervisionReplayAt(i);
                            i += 1;
                            if (i >= firstPath.length) {
                                clearInterval(replayTimer);
                            }
                        }, 400);
                    };

                    if (firstPath.length) {
                        replayMarker = new google.maps.Marker({
                            map,
                            position: { lat: firstPath[0].lat, lng: firstPath[0].lng },
                            title: 'Replay',
                            icon: {
                                path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
                                scale: 5,
                                fillColor: '#fbbf24',
                                fillOpacity: 1,
                                strokeWeight: 1,
                                strokeColor: '#78350f',
                            },
                        });
                    }

                    if (hasPoint) {
                        map.fitBounds(bounds);
                    }
                };

                if (!googleMaps.api_key) {
                    fallback?.classList.remove('hidden');
                    return;
                }
                const script = document.createElement('script');
                script.src = `https://maps.googleapis.com/maps/api/js?key=${googleMaps.api_key}&callback=initSupervisionMap`;
                script.async = true;
                script.defer = true;
                document.head.appendChild(script);
            })();
        </script>
    @endpush
</x-company-layout>
