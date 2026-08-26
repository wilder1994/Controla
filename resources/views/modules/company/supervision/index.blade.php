@php
    $liveJson = json_encode($map['live'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    $historyJson = json_encode($map['history'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    $googleMapsJson = json_encode($map['google_maps'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    $activeTab = in_array($tab ?? '', ['live', 'history', 'summary'], true) ? $tab : 'live';
    $semaphoreClass = match ($summary->semaphore) {
        'green' => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40',
        'yellow' => 'bg-amber-500/20 text-amber-300 border-amber-500/40',
        'red' => 'bg-red-500/20 text-red-300 border-red-500/40',
        default => 'bg-slate-800 text-slate-300 border-slate-700',
    };
@endphp

<x-company-layout title="Supervisión">
    <div class="space-y-4" x-data="{ tab: '{{ $activeTab }}', replayIndex: 0, replayPath: {{ json_encode($map['history'][0]['path'] ?? []) }} }">
        <form method="GET" action="{{ route('company.supervision.index') }}" class="rounded-lg border border-slate-800 bg-slate-900/60 p-3 flex flex-col sm:flex-row sm:items-end gap-3">
            <input type="hidden" name="tab" :value="tab">
            <div>
                <label for="from" class="text-xs text-slate-500">Desde</label>
                <input type="date" id="from" name="from" value="{{ $summary->from }}"
                       class="mt-1 w-full h-9 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white">
            </div>
            <div>
                <label for="to" class="text-xs text-slate-500">Hasta</label>
                <input type="date" id="to" name="to" value="{{ $summary->to }}"
                       class="mt-1 w-full h-9 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white">
            </div>
            <x-ui.button type="submit" size="sm" variant="secondary">Filtrar</x-ui.button>
            <a href="{{ route('company.supervision.report', ['from' => $summary->from, 'to' => $summary->to]) }}"
               class="inline-flex items-center h-9 px-3 text-sm rounded-lg border border-amber-500/40 text-amber-200 hover:bg-amber-500/10">
                Descargar PPTX
            </a>
            <p class="text-xs text-slate-500 sm:ml-auto">
                {{ count($map['live']) }} en vivo · {{ count($map['history']) }} turnos · {{ $summary->reviews }} revistas de campo
            </p>
        </form>

        <div class="flex flex-wrap gap-2">
            <button type="button" class="admin-header-tab" :class="tab === 'live' && 'is-active'" @click="tab = 'live'">En vivo</button>
            <button type="button" class="admin-header-tab" :class="tab === 'history' && 'is-active'" @click="tab = 'history'">Historial / replay</button>
            <button type="button" class="admin-header-tab" :class="tab === 'summary' && 'is-active'" @click="tab = 'summary'">Resumen</button>
        </div>

        <div id="supervision-map" class="w-full h-[420px] rounded-lg border border-slate-800 bg-slate-950/60 overflow-hidden relative"
             x-show="tab !== 'summary'">
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

        <section class="space-y-4" x-show="tab === 'summary'" x-cloak>
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
                <div class="rounded-lg border p-3 {{ $semaphoreClass }}">
                    <p class="text-[10px] uppercase tracking-wide">Cobertura</p>
                    <p class="text-2xl font-semibold tabular-nums">{{ $summary->coveragePercent !== null ? number_format($summary->coveragePercent, 1).'%' : '—' }}</p>
                    <p class="text-[11px] opacity-80">{{ $summary->sitesVisited }}/{{ $summary->sitesContracted }} sitios</p>
                </div>
                <div class="rounded-lg border border-slate-800 bg-slate-950/60 p-3">
                    <p class="text-[10px] uppercase tracking-wide text-slate-500">Revistas</p>
                    <p class="text-2xl font-semibold text-white tabular-nums">{{ $summary->reviews }}</p>
                </div>
                <div class="rounded-lg border border-slate-800 bg-slate-950/60 p-3">
                    <p class="text-[10px] uppercase tracking-wide text-slate-500">Registros campo</p>
                    <p class="text-2xl font-semibold text-white tabular-nums">{{ $summary->fieldLogs }}</p>
                </div>
                <div class="rounded-lg border border-slate-800 bg-slate-950/60 p-3">
                    <p class="text-[10px] uppercase tracking-wide text-slate-500">Km</p>
                    <p class="text-2xl font-semibold text-amber-300 tabular-nums">{{ $summary->kmTraveled }}</p>
                </div>
                <div class="rounded-lg border border-slate-800 bg-slate-950/60 p-3">
                    <p class="text-[10px] uppercase tracking-wide text-slate-500">Recs. abiertas</p>
                    <p class="text-2xl font-semibold text-white tabular-nums">{{ $summary->recommendations['open'] }}</p>
                    <p class="text-[11px] text-slate-500">{{ $summary->recommendations['overdue'] }} vencidas</p>
                </div>
            </div>

            <div class="rounded-lg border border-slate-800 bg-slate-900/80 p-4">
                <h3 class="text-sm font-semibold text-white">Ocho módulos</h3>
                <p class="text-xs text-slate-500 mt-1">Revista de campo + siete registros. No incluye revista de portería (código Accesos).</p>
                <div class="mt-3 grid grid-cols-2 md:grid-cols-4 gap-2">
                    @foreach ($summary->modules as $row)
                        <div class="rounded-md border border-slate-800 bg-slate-950/50 p-3">
                            <p class="text-xs text-amber-200/90">{{ $row['label'] }}</p>
                            <p class="text-xl font-semibold text-white tabular-nums">{{ $row['total'] }}</p>
                            <p class="text-[11px] text-slate-500">OK {{ $row['ok'] }} · Aten. {{ $row['attention'] }} · Crít. {{ $row['critical'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div class="rounded-lg border border-slate-800 bg-slate-900/80 p-4">
                    <h3 class="text-sm font-semibold text-white">Por supervisor</h3>
                    <ul class="mt-3 space-y-2 text-sm">
                        @forelse ($summary->bySupervisor as $row)
                            <li class="flex justify-between gap-2 text-slate-300">
                                <span>{{ $row['name'] }}</span>
                                <span class="text-slate-500 tabular-nums">{{ $row['reviews'] }} rev. · {{ $row['logs'] }} logs · {{ $row['km'] }} km</span>
                            </li>
                        @empty
                            <li class="text-slate-500">Sin actividad en el periodo.</li>
                        @endforelse
                    </ul>
                </div>
                <div class="rounded-lg border border-slate-800 bg-slate-900/80 p-4">
                    <h3 class="text-sm font-semibold text-white">Sitios sin revista</h3>
                    <ul class="mt-3 space-y-2 text-sm text-slate-300">
                        @forelse ($summary->unvisitedSites as $name)
                            <li>{{ $name }}</li>
                        @empty
                            <li class="text-slate-500">Todos los sitios con Supervisión tienen revista en el periodo.</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <div class="rounded-lg border border-slate-800 bg-slate-900/80 p-4">
                <h3 class="text-sm font-semibold text-white">Alertas</h3>
                <ul class="mt-3 space-y-2 text-sm text-slate-300">
                    @foreach ($summary->alerts as $alert)
                        <li>{{ $alert }}</li>
                    @endforeach
                </ul>
            </div>
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
