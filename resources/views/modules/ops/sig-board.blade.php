@php
    $board = $sigBoard ?? [];
    $compact = $compact ?? false;
    $chart = $board['chart'] ?? ['labels' => [], 'values' => []];
    $markers = $board['markers'] ?? [];
@endphp

<div class="space-y-4">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="rounded-lg border border-slate-800 bg-slate-900/80 px-3 py-3">
            <p class="text-[10px] uppercase tracking-wide text-slate-500">Instalaciones</p>
            <p class="mt-1 text-2xl font-semibold text-white tabular-nums">{{ $board['installations_count'] ?? 0 }}</p>
        </div>
        <div class="rounded-lg border border-slate-800 bg-slate-900/80 px-3 py-3">
            <p class="text-[10px] uppercase tracking-wide text-slate-500">Puestos</p>
            <p class="mt-1 text-2xl font-semibold text-white tabular-nums">{{ $board['posts_count'] ?? 0 }}</p>
        </div>
        <div class="rounded-lg border border-slate-800 bg-slate-900/80 px-3 py-3">
            <p class="text-[10px] uppercase tracking-wide text-slate-500">Personal operativo</p>
            <p class="mt-1 text-2xl font-semibold text-white tabular-nums">{{ $board['staff_count'] ?? 0 }}</p>
        </div>
        <div class="rounded-lg border border-slate-800 bg-slate-900/80 px-3 py-3">
            <p class="text-[10px] uppercase tracking-wide text-slate-500">Servicios (12 meses)</p>
            <p class="mt-1 text-2xl font-semibold text-white tabular-nums">{{ array_sum($chart['values'] ?? []) }}</p>
            <p class="text-[10px] text-slate-500">Revistas de puesto</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 {{ $compact ? '' : 'lg:items-stretch' }}">
        <div class="rounded-lg border border-slate-800 bg-slate-900/80 overflow-hidden min-h-[16rem]">
            <div id="sig-map" class="w-full h-64 lg:h-full min-h-[16rem]"></div>
        </div>
        <div class="rounded-lg border border-slate-800 bg-slate-900/80 p-4 flex flex-col max-h-[20rem]">
            <h3 class="text-sm font-semibold text-white shrink-0">Novedades de servicio</h3>
            <div class="mt-3 flex-1 min-h-0 overflow-y-auto space-y-2">
                @forelse (($board['feed'] ?? []) as $row)
                    <div class="rounded-xl border border-slate-800 bg-slate-950/70 px-3 py-2">
                        <p class="text-[10px] text-slate-500">{{ $row['at'] }}</p>
                        <p class="text-sm text-slate-200">{{ $row['body'] }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Aún no hay cambios de servicio.</p>
                @endforelse
            </div>
        </div>
    </div>

    @unless ($compact)
        <div class="rounded-lg border border-slate-800 bg-slate-900/80 p-4">
            <h3 class="text-sm font-semibold text-white">Servicios por mes</h3>
            <div class="h-48 mt-2"><canvas id="sig-chart"></canvas></div>
        </div>

        <div class="rounded-lg border border-slate-800 bg-slate-900/80 overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-800">
                <h3 class="text-sm font-semibold text-white">Salud afiliatoria</h3>
                <p class="text-xs text-slate-500">EPS, pensión, caja y última planilla de los asignados a estos puestos.</p>
            </div>
            <div class="overflow-x-auto max-h-[20rem] overflow-y-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-xs uppercase text-slate-500 sticky top-0 bg-slate-900">
                        <tr>
                            <th class="px-3 py-2 text-left">Empleado</th>
                            <th class="px-3 py-2 text-left">Puesto</th>
                            <th class="px-3 py-2 text-left">EPS</th>
                            <th class="px-3 py-2 text-left">Pensión</th>
                            <th class="px-3 py-2 text-left">Caja</th>
                            <th class="px-3 py-2 text-left">Parafiscal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @forelse (($board['staff'] ?? []) as $row)
                            <tr>
                                <td class="px-3 py-2 text-slate-200">{{ $row['name'] }}</td>
                                <td class="px-3 py-2 text-slate-400">{{ $row['post'] }} · {{ $row['site'] }}</td>
                                <td class="px-3 py-2 {{ $row['eps'] === '—' ? 'text-amber-400' : 'text-slate-300' }}">{{ $row['eps'] }}</td>
                                <td class="px-3 py-2 {{ $row['pension'] === '—' ? 'text-amber-400' : 'text-slate-300' }}">{{ $row['pension'] }}</td>
                                <td class="px-3 py-2 {{ $row['caja'] === '—' ? 'text-amber-400' : 'text-slate-300' }}">{{ $row['caja'] }}</td>
                                <td class="px-3 py-2 {{ ($row['ok'] ?? false) ? 'text-emerald-400' : 'text-amber-400' }}">{{ $row['parafiscal'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-3 py-8 text-center text-slate-500">Sin personal en puestos de este alcance.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endunless
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(() => {
    const markers = @json($markers);
    const maps = @json($board['maps'] ?? []);
    const chart = @json($chart);
    const key = maps.api_key || '';
    const center = maps.center || { lat: 4.6097, lng: -74.0817 };

    function drawChart() {
        const el = document.getElementById('sig-chart');
        if (!el || typeof Chart === 'undefined') return;
        new Chart(el, {
            type: 'bar',
            data: {
                labels: chart.labels || [],
                datasets: [{ label: 'Revistas', data: chart.values || [], backgroundColor: '#2dd4bf' }],
            },
            options: { plugins: { legend: { display: false } }, scales: { x: { ticks: { color: '#94a3b8' } }, y: { ticks: { color: '#94a3b8' }, beginAtZero: true } } },
        });
    }

    function drawMap() {
        const el = document.getElementById('sig-map');
        if (!el || typeof google === 'undefined' || !google.maps) return;
        const map = new google.maps.Map(el, { zoom: 11, center, mapTypeId: 'roadmap' });
        const bounds = new google.maps.LatLngBounds();
        (markers || []).forEach((m) => {
            const pos = { lat: m.lat, lng: m.lng };
            new google.maps.Marker({ map, position: pos, title: m.name });
            bounds.extend(pos);
        });
        if (markers.length) map.fitBounds(bounds);
    }

    if (key) {
        const s = document.createElement('script');
        s.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(key);
        s.async = true;
        s.onload = () => { drawMap(); drawChart(); };
        document.head.appendChild(s);
    } else {
        document.addEventListener('DOMContentLoaded', drawChart);
    }
})();
</script>
@endpush
