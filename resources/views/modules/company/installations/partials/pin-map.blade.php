@php
    $mapId = $mapId ?? 'installation-pin-map';
    $callback = $callback ?? 'initInstallationPinMap';
    $cali = $installation->hasCoordinates()
        ? app(\App\Support\Geo\CaliComunaLayer::class)->locate(
            (float) $installation->latitude,
            (float) $installation->longitude,
        )
        : null;
@endphp
<div class="min-w-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-900 relative">
    @if ($installation->hasCoordinates() && $maps['api_key'])
        <div id="{{ $mapId }}" class="h-96 w-full" data-cali-comunas-url="{{ route('geo.cali-comunas') }}"></div>
        @if ($cali)
            <p class="absolute left-3 bottom-3 z-10 rounded-md bg-slate-950/80 px-2 py-1 text-[11px] text-sky-200">
                {{ $cali['name'] }} · IDESC Cali
            </p>
        @endif
    @elseif ($installation->hasCoordinates())
        <p class="p-4 text-xs text-slate-500">
            Pin {{ $installation->latitude }}, {{ $installation->longitude }}. Configura <code class="text-indigo-300">GOOGLE_MAPS_API_KEY</code> para ver el mapa.
        </p>
    @else
        <p class="p-4 text-sm text-slate-500">Esta instalación no tiene pin.</p>
    @endif
</div>
