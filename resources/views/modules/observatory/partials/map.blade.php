@php
    $map = $map ?? ['google_maps' => ['api_key' => null], 'sites' => []];
    $maps = $map['google_maps'] ?? [];
    $sites = $map['sites'] ?? [];
    $points = $map['points'] ?? [];
    $showLegend = $showLegend ?? false;
    $mapCanvasClass = $mapCanvasClass ?? 'h-80 xl:h-full xl:min-h-[24rem]';
@endphp
<div class="obs-card overflow-hidden h-full min-h-80 flex flex-col">
    @if (! empty($maps['api_key']))
        <div
            x-data="observatoryMap(@js([
                'sites' => $sites,
                'points' => $points,
                'center' => $maps['center'] ?? ['lat' => 4.5709, 'lng' => -74.2973],
                'zoom' => $maps['zoom'] ?? 6,
            ]))"
            class="relative flex-1 min-h-80 flex flex-col"
        >
            <div class="shrink-0 flex flex-wrap items-center justify-between gap-2 px-2.5 py-2 border-b border-slate-800 bg-slate-950/80">
                <div class="flex gap-1">
                    <button type="button" class="h-7 px-2.5 rounded-md text-[11px] font-semibold"
                            :class="mode === 'pins' ? 'bg-white text-slate-900' : 'bg-slate-900 text-slate-200 border border-slate-700'"
                            @click="setMode('pins')">Pines</button>
                    <button type="button" class="h-7 px-2.5 rounded-md text-[11px] font-semibold"
                            :class="mode === 'heat' ? 'bg-white text-slate-900' : 'bg-slate-900 text-slate-200 border border-slate-700'"
                            @click="setMode('heat')">Calor</button>
                </div>
                <div class="flex gap-1">
                    <button type="button" class="h-7 px-2.5 rounded-md text-[11px] font-semibold"
                            :class="mapType === 'roadmap' ? 'bg-white text-slate-900' : 'bg-slate-900 text-slate-200 border border-slate-700'"
                            @click="setMapType('roadmap')">Mapa</button>
                    <button type="button" class="h-7 px-2.5 rounded-md text-[11px] font-semibold"
                            :class="mapType === 'satellite' ? 'bg-white text-slate-900' : 'bg-slate-900 text-slate-200 border border-slate-700'"
                            @click="setMapType('satellite')">Satélite</button>
                </div>
            </div>
            <div x-ref="map" class="{{ $mapCanvasClass }} w-full flex-1"></div>
            @if ($showLegend)
                <p class="px-3 py-2 text-[11px] text-slate-500 border-t border-slate-800">
                    Ámbar nuevo · índigo en atención · gris cerrado. Calor = ubicación de cada reporte abierto.
                </p>
            @endif
        </div>
        @push('scripts')
            <script>
                window.initObservatoryMap = window.initObservatoryMap || function () {
                    window.__observatoryMapReady = true;
                };
            </script>
            <script src="https://maps.googleapis.com/maps/api/js?key={{ $maps['api_key'] }}&libraries=visualization&callback=initObservatoryMap" async defer></script>
        @endpush
    @elseif (count($sites) > 0 || count($points) > 0)
        <p class="p-4 text-xs text-slate-500">
            {{ count($sites) + count($points) }} puntos. Configura <code class="text-indigo-300">GOOGLE_MAPS_API_KEY</code> para ver el mapa.
        </p>
    @else
        <p class="p-4 text-sm text-slate-500">No hay colegios con coordenadas.</p>
    @endif
</div>
