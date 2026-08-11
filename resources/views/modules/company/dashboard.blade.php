@php
    $endsAt = $metrics['package_ends_at'] ?? null;
    $daysLeft = $metrics['days_until_renewal'];
    $usagePct = min(100, (float) ($metrics['usage_ratio'] ?? 0));
    $statusColor = ($metrics['is_expired'] ?? false)
        ? 'text-red-400'
        : (($metrics['is_renewal_soon'] ?? false) ? 'text-amber-400' : 'text-emerald-400');
    $ops = $ops ?? [];
    $k = $ops['kpis'] ?? [];
    $access = $ops['access'] ?? [];
    $workforce = $ops['workforce'] ?? [];
    $attention = $ops['attention'] ?? [];
    $mapMarkers = $ops['map_markers'] ?? [];
    $portfolio = $ops['portfolio'] ?? [];
    $complianceByClient = $ops['compliance_by_client'] ?? [];
    $revistaTrend = $ops['revista_trend'] ?? ['labels' => [], 'done' => [], 'expected' => []];
    $accessByClient = $ops['access_by_client'] ?? [];
    $openShiftsTable = $ops['open_shifts_table'] ?? [];
    $mapMarkersJson = json_encode($mapMarkers, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    $googleMapsJson = json_encode($ops['google_maps'] ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    $complianceJson = json_encode($complianceByClient, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    $revistaTrendJson = json_encode($revistaTrend, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    $accessChartJson = json_encode($accessByClient, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

    $panicOpen = (int) ($k['panics_open'] ?? 0);
    $panicsToday = (int) ($k['panics_today'] ?? 0);
    $blockV = (int) ($k['blocklist_vehicles'] ?? 0);
    $blockP = (int) ($k['blocklist_persons'] ?? 0);
    $sinRevista = (int) ($k['without_revista_on_shift'] ?? 0);
    $corrPending = (int) ($k['pending_correspondence'] ?? 0);
    $sinAsign = (int) ($workforce['without_assignment'] ?? 0);
@endphp

<x-company-layout title="Resumen empresa">
    @push('styles')
    <style>
        .company-command-center { max-width: 80rem; }
        .company-map-shell {
            height: 280px;
            min-height: 280px;
            max-height: 280px;
            position: relative;
            background: #020617;
        }
        .company-map-shell #company-map,
        .company-map-shell #company-map-svg {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
        }
        .company-panel-fixed-sm { min-height: 112px; }
        .company-panel-attention {
            min-height: 200px;
            max-height: 200px;
        }
        .company-panel-attention .company-panel-body {
            height: 156px;
            overflow-y: auto;
        }
        .company-panel-workforce { min-height: 112px; }
        .company-panel-chart { height: 140px; }
        .company-panel-chart-wrap { min-height: 188px; }
        .company-panel-shifts {
            min-height: 220px;
            max-height: 220px;
        }
        .company-panel-shifts .company-panel-body {
            height: 176px;
            overflow-y: auto;
        }
        .company-pin-detail { min-height: 108px; }
    </style>
    @endpush

    <div class="company-command-center space-y-3">
        {{-- Barra licencia compacta --}}
        <section class="rounded-lg border border-slate-800 bg-slate-900/60 px-3 py-2">
            <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-slate-400">
                    <span class="font-medium {{ $statusColor }}">{{ $metrics['subscription_status_label'] }}</span>
                    <span class="text-slate-600">·</span>
                    <span>{{ $metrics['package_label'] }}</span>
                    <span class="text-slate-600">·</span>
                    <span>{{ $k['active_clients'] ?? 0 }}/{{ $k['max_clients'] ?? 0 }} cupo</span>
                    <span class="text-slate-600">·</span>
                    <span>{{ $k['archived_clients'] ?? 0 }} archivados</span>
                    @can('company.billing.manage')
                        <a href="{{ route('company.billing.index') }}" class="text-indigo-400 hover:text-indigo-300">Facturación</a>
                    @endcan
                </div>
                @if ($endsAt)
                    <span class="text-slate-500 shrink-0">
                        Renueva {{ $endsAt->format('d M Y') }}
                        @if ($daysLeft !== null && $daysLeft >= 0)
                            ({{ $daysLeft }} d)
                        @endif
                    </span>
                @endif
            </div>
            <div class="mt-2 h-1 rounded-full bg-slate-800 overflow-hidden">
                <div class="h-full rounded-full {{ $usagePct >= 90 ? 'bg-amber-500' : 'bg-indigo-500' }}"
                     style="width: {{ max($usagePct, ($k['active_clients'] ?? 0) > 0 ? 2 : 0) }}%"></div>
            </div>
        </section>

        {{-- 8 KPIs operación --}}
        <section>
            <div class="flex items-center justify-between gap-2 mb-1.5">
                <h3 class="text-sm font-semibold text-white">Cartera y operación</h3>
                <p class="text-xs text-slate-500 truncate max-w-[50%]">{{ $access['scope_note'] ?? '' }}</p>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
                <x-company.kpi-stat label="Conjuntos activos / cupo" :value="($k['active_clients'] ?? 0).' / '.($k['max_clients'] ?? 0)" />
                <x-company.kpi-stat label="Conjuntos archivados" :value="$k['archived_clients'] ?? 0" />
                <x-company.kpi-stat label="Vigilantes en turno ahora" :value="$k['vigilantes_on_shift'] ?? 0" tone="info" />
                <x-company.kpi-stat label="Puestos con turno abierto" :value="$k['posts_open'] ?? 0" />
                <x-company.kpi-stat label="Vehículos · entradas hoy" :value="$k['vehicle_entries_today'] ?? 0" />
                <x-company.kpi-stat label="Visitantes peatonales · entradas hoy" :value="$k['visitor_entries_today'] ?? 0" />
                <x-company.kpi-stat label="Novedades minuta hoy" :value="$k['novedades_today'] ?? 0" />
                <x-company.kpi-stat label="Correspondencia pendiente" :value="$corrPending" :tone="$corrPending > 0 ? 'warning' : 'neutral'" />
            </div>
        </section>

        {{-- 4 KPIs seguridad --}}
        <section>
            <div class="flex items-center justify-between gap-2 mb-1.5">
                <h3 class="text-sm font-semibold text-white">Seguridad y lista de bloqueo</h3>
                <p class="text-xs text-slate-500">Pánico = emergencia · Bloqueos = lista negra</p>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
                <x-company.kpi-stat label="Pánicos abiertos sin cerrar" :value="$panicOpen" :tone="$panicOpen > 0 ? 'danger' : 'neutral'" />
                <x-company.kpi-stat label="Pánicos registrados hoy" :value="$panicsToday" :tone="$panicsToday > 0 ? 'warning' : 'neutral'" />
                <x-company.kpi-stat label="Bloqueos · vehículos activos" :value="$blockV" :tone="$blockV > 0 ? 'warning' : 'neutral'" />
                <x-company.kpi-stat label="Bloqueos · personas activas" :value="$blockP" :tone="$blockP > 0 ? 'warning' : 'neutral'" />
            </div>
        </section>

        {{-- 4 KPIs revista --}}
        <section>
            <div class="flex items-center justify-between gap-2 mb-1.5">
                <h3 class="text-sm font-semibold text-white">Revista (KPIs)</h3>
                <p class="text-xs text-slate-500">Detalle → módulo Revista supervisor</p>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
                <x-company.kpi-stat
                    label="Cumplimiento revistas (mes)"
                    :value="number_format((float) ($k['revista_compliance_pct'] ?? 0), 1).'%'"
                    :tone="($k['revista_compliance_pct'] ?? 0) >= 80 ? 'success' : 'neutral'"
                />
                <x-company.kpi-stat
                    label="Revistas hoy / esperadas hoy"
                    :value="($k['revistas_today_done'] ?? 0).' / '.($k['revistas_today_expected'] ?? 0)"
                />
                <x-company.kpi-stat label="Sin revista en turno" :value="$sinRevista" :tone="$sinRevista > 0 ? 'warning' : 'neutral'" />
                <x-company.kpi-stat
                    label="Supervisores en ruta hoy"
                    :value="($k['supervisors_on_route_today'] ?? 0).' / '.($k['supervisors_active'] ?? 0)"
                />
            </div>
        </section>

        {{-- Mapa + columna derecha (canvas) --}}
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-3">
            <div class="xl:col-span-7 space-y-2">
                <div class="flex items-center justify-between gap-2">
                    <h3 class="text-sm font-semibold text-white">Mapa de conjuntos</h3>
                    <p class="text-xs text-slate-500">
                        Salud compuesta · {{ $portfolio['with_geo'] ?? 0 }}/{{ $portfolio['active_total'] ?? 0 }} con ubicación
                    </p>
                </div>
                <div class="rounded-lg border border-slate-800 bg-slate-900/80 overflow-hidden">
                    <div class="company-map-shell">
                        <div id="company-map" class="hidden"></div>
                        <svg id="company-map-svg" viewBox="0 0 100 100" preserveAspectRatio="none" class="opacity-90"></svg>
                        <div id="company-map-empty" class="absolute inset-0 flex items-center justify-center text-center p-4 text-xs text-slate-500 hidden">
                            Sin conjuntos con coordenadas
                        </div>
                    </div>
                </div>

                <div id="company-pin-detail" class="company-pin-detail rounded-lg border border-slate-800 bg-slate-900/80 px-3 py-2.5">
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <p class="text-sm font-semibold text-white truncate" data-pin="title">Selecciona un conjunto</p>
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="inline-flex rounded-full bg-slate-800 px-2 py-0.5 text-xs text-slate-400" data-pin="tone">—</span>
                            <a data-pin="ver" href="#" class="text-xs text-indigo-400 hover:text-indigo-300 pointer-events-none opacity-40">Ver</a>
                            <form data-pin="operate-form" method="POST" action="#" class="inline">
                                @csrf
                                <button type="submit" data-pin="operar" class="text-xs text-emerald-400 hover:text-emerald-300 disabled:opacity-40" disabled>Operar</button>
                            </form>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-x-4 gap-y-0.5 text-xs text-slate-500">
                        <p>Revistas hoy: <span class="text-slate-300" data-pin="revistas">—</span></p>
                        <p>Turno abierto: <span class="text-slate-300" data-pin="turno">—</span></p>
                        <p>Vehículos hoy: <span class="text-slate-300" data-pin="vehiculos">—</span></p>
                        <p>Visitantes hoy: <span class="text-slate-300" data-pin="visitantes">—</span></p>
                        <p>Inicio servicio: <span class="text-slate-300" data-pin="inicio">—</span></p>
                    </div>
                    <p class="mt-1 text-xs text-slate-500 truncate">Última novedad: <span class="text-slate-400" data-pin="novedad">—</span></p>
                </div>
            </div>

            <div class="xl:col-span-5 space-y-2">
                <div class="rounded-lg border border-slate-800 bg-slate-900/80 overflow-hidden company-panel-attention">
                    <div class="px-3 py-2 border-b border-slate-800">
                        <h3 class="text-sm font-semibold text-white">Atención ahora</h3>
                    </div>
                    <div class="company-panel-body">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-950/60 text-xs text-slate-500 sticky top-0">
                                <tr>
                                    <th class="px-3 py-1.5 text-left font-medium">Prioridad</th>
                                    <th class="px-3 py-1.5 text-left font-medium">Señal</th>
                                    <th class="px-3 py-1.5 text-left font-medium">Contexto</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800">
                                @forelse ($attention as $item)
                                    <tr class="hover:bg-slate-800/30">
                                        @php
                                            $toneClass = match ($item['tone'] ?? 'neutral') {
                                                'danger' => 'text-red-300',
                                                'warning' => 'text-amber-300',
                                                'info' => 'text-indigo-300',
                                                default => 'text-slate-400',
                                            };
                                        @endphp
                                        <td class="px-3 py-2"><span class="text-xs font-medium {{ $toneClass }}">{{ $item['priority'] }}</span></td>
                                        <td class="px-3 py-2 text-xs text-slate-300">{{ $item['signal'] }}</td>
                                        <td class="px-3 py-2 text-xs text-slate-500">{{ $item['context'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-3 py-8 text-center text-xs text-slate-500">Sin alertas operativas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-lg border border-slate-800 bg-slate-900/80 px-3 py-2.5 company-panel-workforce">
                    <h3 class="text-sm font-semibold text-white mb-2">Fuerza laboral</h3>
                    <div class="grid grid-cols-4 gap-2 text-center sm:text-left">
                        <div>
                            <p class="text-xs text-slate-500">Vigilantes</p>
                            <p class="text-sm font-semibold text-white tabular-nums">{{ $workforce['vigilantes_active'] ?? 0 }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">En turno</p>
                            <p class="text-sm font-semibold text-indigo-300 tabular-nums">{{ $workforce['vigilantes_on_shift'] ?? 0 }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">Supervisores</p>
                            <p class="text-sm font-semibold text-white tabular-nums">{{ $workforce['supervisors_active'] ?? 0 }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">Sin asignación</p>
                            <p class="text-sm font-semibold tabular-nums {{ $sinAsign > 0 ? 'text-amber-400' : 'text-white' }}">{{ $sinAsign }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border border-slate-800 bg-slate-900/80 overflow-hidden company-panel-chart-wrap">
                    <div class="px-3 py-2 border-b border-slate-800">
                        <h3 class="text-sm font-semibold text-white">Cumplimiento por conjunto</h3>
                    </div>
                    <div class="px-3 py-2 company-panel-chart">
                        <canvas id="companyComplianceChart" aria-label="Cumplimiento por conjunto"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Fila inferior 3 paneles --}}
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-3">
            <div class="rounded-lg border border-slate-800 bg-slate-900/80 overflow-hidden company-panel-chart-wrap">
                <div class="px-3 py-2 border-b border-slate-800">
                    <h3 class="text-sm font-semibold text-white">Revistas: realizadas vs esperadas</h3>
                </div>
                <div class="px-3 py-2 company-panel-chart">
                    <canvas id="companyRevistaTrendChart" aria-label="Tendencia revistas"></canvas>
                </div>
            </div>

            <div class="rounded-lg border border-slate-800 bg-slate-900/80 overflow-hidden company-panel-chart-wrap">
                <div class="px-3 py-2 border-b border-slate-800">
                    <h3 class="text-sm font-semibold text-white">Accesos por conjunto (hoy)</h3>
                </div>
                <div class="px-3 py-2 company-panel-chart">
                    <canvas id="companyAccessChart" aria-label="Accesos por conjunto"></canvas>
                </div>
            </div>

            <div class="rounded-lg border border-slate-800 bg-slate-900/80 overflow-hidden company-panel-shifts">
                <div class="px-3 py-2 border-b border-slate-800">
                    <h3 class="text-sm font-semibold text-white">Turnos abiertos ahora</h3>
                </div>
                <div class="company-panel-body">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-950/60 text-xs text-slate-500 sticky top-0">
                            <tr>
                                <th class="px-3 py-1.5 text-left font-medium">Puesto</th>
                                <th class="px-3 py-1.5 text-left font-medium">Vigilante</th>
                                <th class="px-3 py-1.5 text-left font-medium">Inicio</th>
                                <th class="px-3 py-1.5 text-left font-medium">Últ. revista</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800">
                            @forelse ($openShiftsTable as $row)
                                <tr class="hover:bg-slate-800/30">
                                    <td class="px-3 py-2 text-xs text-slate-300">{{ $row['puesto'] }}</td>
                                    <td class="px-3 py-2 text-xs text-slate-400">{{ $row['vigilante'] }}</td>
                                    <td class="px-3 py-2 text-xs text-slate-400">{{ $row['inicio'] }}</td>
                                    <td class="px-3 py-2 text-xs {{ ($row['tone'] ?? '') === 'danger' ? 'text-red-300' : 'text-emerald-300' }}">{{ $row['ultima_revista'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-3 py-8 text-center text-xs text-slate-500">Sin turnos abiertos.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            const chartDefaults = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { labels: { color: '#94a3b8', boxWidth: 10, font: { size: 10 } } } },
                scales: {
                    x: { ticks: { color: '#64748b', font: { size: 9 }, maxRotation: 0 }, grid: { color: '#1e293b' } },
                    y: { beginAtZero: true, ticks: { color: '#64748b', precision: 0, font: { size: 9 } }, grid: { color: '#1e293b' } },
                },
            };

            function barChart(el, labels, datasets, yMax) {
                if (!el) return;
                const opts = { ...chartDefaults };
                if (yMax) {
                    opts.scales = { ...opts.scales, y: { ...opts.scales.y, max: yMax } };
                }
                new Chart(el, { type: 'bar', data: { labels, datasets }, options: opts });
            }

            const complianceRows = {!! $complianceJson !!};
            const complianceLabels = complianceRows.length
                ? complianceRows.map(r => r.label.length > 10 ? r.label.slice(0, 10) + '…' : r.label)
                : ['—'];
            const complianceData = complianceRows.length ? complianceRows.map(r => r.value) : [0];
            barChart(
                document.getElementById('companyComplianceChart'),
                complianceLabels,
                [{ label: 'Cumplimiento mes (%)', data: complianceData, backgroundColor: '#6366f1' }],
                100
            );

            const trend = {!! $revistaTrendJson !!};
            const trendEl = document.getElementById('companyRevistaTrendChart');
            if (trendEl) {
                new Chart(trendEl, {
                    type: 'line',
                    data: {
                        labels: trend.labels?.length ? trend.labels : ['—'],
                        datasets: [
                            { label: 'Realizadas', data: trend.done?.length ? trend.done : [0], borderColor: '#34d399', backgroundColor: 'rgba(52,211,153,0.12)', fill: true, tension: 0.3, pointRadius: 2 },
                            { label: 'Esperadas', data: trend.expected?.length ? trend.expected : [0], borderColor: '#64748b', backgroundColor: 'transparent', tension: 0.3, pointRadius: 2 },
                        ],
                    },
                    options: chartDefaults,
                });
            }

            const accessRows = {!! $accessChartJson !!};
            const accessLabels = accessRows.length
                ? accessRows.map(r => r.label.length > 10 ? r.label.slice(0, 10) + '…' : r.label)
                : ['—'];
            barChart(
                document.getElementById('companyAccessChart'),
                accessLabels,
                accessRows.length
                    ? [
                        { label: 'Vehículos', data: accessRows.map(r => r.vehicles), backgroundColor: '#6366f1' },
                        { label: 'Visitantes', data: accessRows.map(r => r.visitors), backgroundColor: '#475569' },
                    ]
                    : [{ label: 'Accesos', data: [0], backgroundColor: '#334155' }]
            );

            const mapMarkers = {!! $mapMarkersJson !!};
            const googleMaps = {!! $googleMapsJson !!};
            const mapEl = document.getElementById('company-map');
            const svgEl = document.getElementById('company-map-svg');
            const emptyEl = document.getElementById('company-map-empty');
            const toneColor = { ok: '#34d399', warn: '#fbbf24', danger: '#f87171' };

            function fillPin(pin) {
                const root = document.getElementById('company-pin-detail');
                if (!root || !pin) return;
                root.querySelector('[data-pin="title"]').textContent = pin.title;
                root.querySelector('[data-pin="tone"]').textContent = pin.tone_label || '—';
                root.querySelector('[data-pin="revistas"]').textContent = pin.revistas_hoy;
                root.querySelector('[data-pin="turno"]').textContent = pin.turno_abierto ? 'Sí' : 'No';
                root.querySelector('[data-pin="vehiculos"]').textContent = pin.vehiculos_hoy;
                root.querySelector('[data-pin="visitantes"]').textContent = pin.visitantes_hoy;
                root.querySelector('[data-pin="inicio"]').textContent = pin.service_started;
                root.querySelector('[data-pin="novedad"]').textContent = pin.ultima_novedad;
                const ver = root.querySelector('[data-pin="ver"]');
                ver.href = pin.url;
                ver.classList.remove('pointer-events-none', 'opacity-40');
                const form = root.querySelector('[data-pin="operate-form"]');
                root.querySelector('[data-pin="operar"]').disabled = false;
                form.action = pin.operate_url;
            }

            function normalizePins(pins) {
                if (!pins.length) return [];
                const lats = pins.map(p => p.lat);
                const lngs = pins.map(p => p.lng);
                const minLat = Math.min(...lats), maxLat = Math.max(...lats);
                const minLng = Math.min(...lngs), maxLng = Math.max(...lngs);
                const pad = 8;
                return pins.map(p => {
                    const x = maxLng === minLng ? 50 : pad + ((p.lng - minLng) / (maxLng - minLng)) * (100 - 2 * pad);
                    const y = maxLat === minLat ? 50 : pad + ((maxLat - p.lat) / (maxLat - minLat)) * (100 - 2 * pad);
                    return { ...p, nx: x, ny: y };
                });
            }

            function drawSvgMap(pins) {
                if (!svgEl) return;
                const norm = normalizePins(pins);
                let html = '<rect width="100" height="100" fill="#020617"/>';
                html += '<path d="M8 70 Q25 40 40 55 T70 35 T95 50" fill="none" stroke="#334155" stroke-width="0.4"/>';
                norm.forEach(pin => {
                    const c = toneColor[pin.tone] || '#6366f1';
                    html += `<circle cx="${pin.nx}" cy="${pin.ny}" r="3.5" fill="${c}" stroke="#0f172a" stroke-width="0.6" data-pin-id="${pin.id}" style="cursor:pointer"/>`;
                });
                svgEl.innerHTML = html;
                svgEl.querySelectorAll('circle[data-pin-id]').forEach(node => {
                    const id = Number(node.getAttribute('data-pin-id'));
                    const pin = pins.find(p => p.id === id);
                    if (pin) node.addEventListener('click', () => fillPin(pin));
                });
            }

            function initGoogleMap() {
                if (!googleMaps.api_key || !mapMarkers.length) return false;
                mapEl.classList.remove('hidden');
                svgEl.style.opacity = '0.15';
                const map = new google.maps.Map(mapEl, {
                    center: googleMaps.center,
                    zoom: googleMaps.zoom,
                    disableDefaultUI: true,
                    zoomControl: true,
                    styles: [
                        { elementType: 'geometry', stylers: [{ color: '#1e293b' }] },
                        { elementType: 'labels.text.fill', stylers: [{ color: '#94a3b8' }] },
                        { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#0f172a' }] },
                    ],
                });
                const bounds = new google.maps.LatLngBounds();
                mapMarkers.forEach((pin, idx) => {
                    const marker = new google.maps.Marker({
                        position: { lat: pin.lat, lng: pin.lng },
                        map,
                        title: pin.title,
                        icon: {
                            path: google.maps.SymbolPath.CIRCLE,
                            scale: 7,
                            fillColor: toneColor[pin.tone] || '#6366f1',
                            fillOpacity: 1,
                            strokeColor: '#0f172a',
                            strokeWeight: 1.5,
                        },
                    });
                    marker.addListener('click', () => fillPin(pin));
                    bounds.extend(marker.getPosition());
                    if (idx === 0) fillPin(pin);
                });
                if (mapMarkers.length === 1) {
                    map.setCenter({ lat: mapMarkers[0].lat, lng: mapMarkers[0].lng });
                    map.setZoom(14);
                } else {
                    map.fitBounds(bounds, 48);
                }
                return true;
            }

            if (!mapMarkers.length) {
                emptyEl.classList.remove('hidden');
                svgEl.style.display = 'none';
            } else {
                drawSvgMap(mapMarkers);
                if (googleMaps.api_key) {
                    window.initCompanyMap = () => initGoogleMap();
                    const script = document.createElement('script');
                    script.src = `https://maps.googleapis.com/maps/api/js?key=${googleMaps.api_key}&callback=initCompanyMap`;
                    script.async = true;
                    script.defer = true;
                    document.head.appendChild(script);
                } else {
                    fillPin(mapMarkers[0]);
                }
            }
        })();
    </script>
    @endpush
</x-company-layout>
