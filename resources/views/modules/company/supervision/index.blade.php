@php
    $liveJson = json_encode($map['live'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    $historyJson = json_encode($map['history'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    $reviewsJson = json_encode($map['reviews'] ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    $clientsJson = json_encode($map['clients'] ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    $googleMapsJson = json_encode($map['google_maps'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    $activeTab = in_array($tab ?? '', ['live', 'history', 'summary', 'sheets'], true) ? $tab : 'live';
    $tabQuery = array_filter([
        'from' => $summary->from,
        'to' => $summary->to,
        'zone_id' => $filter->zoneId ?? null,
        'supervisor_id' => $filter->supervisorId ?? null,
        'kind' => $filter->sheetKind ?? null,
        'client_id' => $filter->clientId ?? null,
        'novelty' => $filter->hasNovelty === null ? null : ($filter->hasNovelty ? '1' : '0'),
    ], fn ($value) => $value !== null && $value !== '');
    $semaphoreClass = match ($summary->semaphore) {
        'green' => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40',
        'yellow' => 'bg-amber-500/20 text-amber-300 border-amber-500/40',
        'red' => 'bg-red-500/20 text-red-300 border-red-500/40',
        default => 'bg-slate-800 text-slate-300 border-slate-700',
    };
@endphp

<x-company-layout title="Supervisión">
    <x-slot:actions>
        <form method="GET" action="{{ route('company.supervision.index') }}" class="flex flex-wrap items-end gap-2" id="supervision-filter">
            <input type="hidden" name="tab" value="{{ $activeTab }}">
            <div>
                <label for="period-year" class="text-xs text-slate-500">Año</label>
                <select id="period-year"
                        class="mt-1 block h-9 min-w-[5.5rem] px-2 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white">
                    @for ($year = now()->year; $year >= now()->year - 4; $year--)
                        <option value="{{ $year }}" @selected((int) substr($summary->from, 0, 4) === $year)>{{ $year }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <label for="period-month" class="text-xs text-slate-500">Mes</label>
                <select id="period-month"
                        class="mt-1 block h-9 min-w-[7.5rem] px-2 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white">
                    <option value="">Todo el año</option>
                    @foreach (['01'=>'Ene','02'=>'Feb','03'=>'Mar','04'=>'Abr','05'=>'May','06'=>'Jun','07'=>'Jul','08'=>'Ago','09'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Dic'] as $num => $label)
                        <option value="{{ $num }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="from" class="text-xs text-slate-500">Desde</label>
                <input type="date" id="from" name="from" value="{{ $summary->from }}"
                       class="mt-1 block h-9 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white">
            </div>
            <div>
                <label for="to" class="text-xs text-slate-500">Hasta</label>
                <input type="date" id="to" name="to" value="{{ $summary->to }}"
                       class="mt-1 block h-9 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white">
            </div>
            <div class="flex gap-1 pb-0.5">
                <button type="button" data-preset="today" class="h-9 px-2 text-xs rounded-lg border border-slate-700 text-slate-300 hover:bg-slate-800">Hoy</button>
                <button type="button" data-preset="month" class="h-9 px-2 text-xs rounded-lg border border-slate-700 text-slate-300 hover:bg-slate-800">Mes</button>
                <button type="button" data-preset="year" class="h-9 px-2 text-xs rounded-lg border border-slate-700 text-slate-300 hover:bg-slate-800">Año</button>
            </div>
            <div>
                <label for="zone_id" class="text-xs text-slate-500">Zona</label>
                <select id="zone_id" name="zone_id"
                        class="mt-1 block h-9 min-w-[9.5rem] px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white">
                    <option value="">Todas</option>
                    @foreach ($zones ?? [] as $zone)
                        <option value="{{ $zone->id }}" @selected($filter->zoneId === (int) $zone->id)>
                            {{ $zone->name }}{{ $zone->is_active ? '' : ' (inactiva)' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="supervisor_id" class="text-xs text-slate-500">Supervisor</label>
                <select id="supervisor_id" name="supervisor_id"
                        class="mt-1 block h-9 min-w-[9.5rem] px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white">
                    <option value="">Todos</option>
                    @foreach ($supervisors ?? [] as $supervisor)
                        <option value="{{ $supervisor->id }}" @selected($filter->supervisorId === (int) $supervisor->id)>
                            {{ $supervisor->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @if ($activeTab === 'sheets')
                <div>
                    <label for="kind" class="text-xs text-slate-500">Tipo</label>
                    <select id="kind" name="kind"
                            class="mt-1 block h-9 min-w-[8.5rem] px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white">
                        <option value="">Todas</option>
                        <option value="review" @selected($filter->sheetKind === 'review')>Revista</option>
                        <option value="alarm" @selected($filter->sheetKind === 'alarm')>Alarma</option>
                        <option value="support" @selected($filter->sheetKind === 'support')>Apoyo</option>
                        <option value="document" @selected($filter->sheetKind === 'document')>Documentos</option>
                    </select>
                </div>
                <div>
                    <label for="client_id" class="text-xs text-slate-500">Cliente</label>
                    <select id="client_id" name="client_id"
                            class="mt-1 block h-9 min-w-[9.5rem] px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white">
                        <option value="">Todos</option>
                        @foreach ($clients ?? [] as $client)
                            <option value="{{ $client->id }}" @selected($filter->clientId === (int) $client->id)>{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="novelty" class="text-xs text-slate-500">Novedad</label>
                    <select id="novelty" name="novelty"
                            class="mt-1 block h-9 min-w-[7.5rem] px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white">
                        <option value="">Todas</option>
                        <option value="1" @selected($filter->hasNovelty === true)>Con novedad</option>
                        <option value="0" @selected($filter->hasNovelty === false)>Sin novedad</option>
                    </select>
                </div>
            @endif
            <x-ui.button type="submit" size="sm" variant="secondary">Filtrar</x-ui.button>
            <a href="{{ route('company.supervision.report', $tabQuery) }}"
               class="inline-flex items-center h-9 px-3 text-sm rounded-lg border border-amber-500/40 text-amber-200 hover:bg-amber-500/10">
                Descargar PPTX
            </a>
        </form>
    </x-slot:actions>

    <x-slot:headerTabs>
        <a
            href="{{ route('company.supervision.index', $tabQuery + ['tab' => 'live']) }}"
            @class(['admin-header-tab', 'is-active' => $activeTab === 'live'])
        >En vivo</a>
        <a
            href="{{ route('company.supervision.index', $tabQuery + ['tab' => 'history']) }}"
            @class(['admin-header-tab', 'is-active' => $activeTab === 'history'])
        >Historial / replay</a>
        <a
            href="{{ route('company.supervision.index', $tabQuery + ['tab' => 'summary']) }}"
            @class(['admin-header-tab', 'is-active' => $activeTab === 'summary'])
        >Resumen</a>
        <a
            href="{{ route('company.supervision.index', $tabQuery + ['tab' => 'sheets']) }}"
            @class(['admin-header-tab', 'is-active' => $activeTab === 'sheets'])
        >Fichas</a>
    </x-slot:headerTabs>

    <div class="space-y-4">
        @if ($activeTab !== 'summary' && $activeTab !== 'sheets')
            <div id="supervision-map" class="w-full h-[420px] rounded-lg border border-slate-800 bg-slate-950/60 overflow-hidden relative">
                <div class="absolute top-3 left-3 z-10 inline-flex rounded-md border border-slate-700 bg-slate-950/90 p-0.5 text-xs">
                    <button type="button" class="supervision-map-type-btn rounded px-2 py-1 font-medium text-white bg-indigo-600/80" data-map-type="satellite">Satélite</button>
                    <button type="button" class="supervision-map-type-btn rounded px-2 py-1 font-medium text-slate-400 hover:text-slate-200" data-map-type="terrain">Terreno</button>
                </div>
                <div id="supervision-map-fallback" class="absolute inset-0 flex items-center justify-center text-center p-6 text-sm text-slate-500 hidden">
                    <div>
                        <p class="text-slate-300 font-medium mb-1">Mapa no disponible</p>
                        <p>Configura <code class="text-indigo-300">GOOGLE_MAPS_API_KEY</code>.</p>
                    </div>
                </div>
            </div>
        @endif

        @if ($activeTab === 'sheets')
            <section class="rounded-lg border border-slate-800 bg-slate-900/80 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-800">
                    <h3 class="text-sm font-semibold text-white">Fichas de campo</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Carta imprimible. No es el rastro GPS ni el PPTX.</p>
                </div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs font-medium text-slate-500 border-b border-slate-800">
                            <th class="text-left px-4 py-2">Folio</th>
                            <th class="text-left px-4 py-2">Tipo</th>
                            <th class="text-left px-4 py-2">Supervisor</th>
                            <th class="text-left px-4 py-2">Cliente</th>
                            <th class="text-left px-4 py-2">Fecha</th>
                            <th class="text-left px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sheets ?? [] as $row)
                            <tr class="border-b border-slate-800/80">
                                <td class="px-4 py-2 font-mono text-xs text-indigo-300/90">{{ $row->folio }}</td>
                                <td class="px-4 py-2 font-medium text-slate-200">
                                    {{ $row->typeLabel }}
                                    @if ($row->hasNovelty)
                                        <span class="ml-1 text-xs text-red-300">novedad</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-slate-300">{{ $row->supervisorName }}</td>
                                <td class="px-4 py-2 text-slate-400">{{ $row->clientName ?? '—' }}</td>
                                <td class="px-4 py-2 text-slate-400">{{ $row->recordedAt->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-2 text-right">
                                    <a href="{{ route('company.supervision.sheets.show', ['kind' => $row->kind->value, 'id' => $row->id]) }}"
                                       target="_blank"
                                       class="text-indigo-300 hover:text-indigo-200">Ver / imprimir</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-sm text-slate-500">No hay fichas en este recorte.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                @if ($sheets)
                    <div class="px-4 py-3">{{ $sheets->links() }}</div>
                @endif
            </section>
        @endif

        @if ($activeTab === 'live')
            <section class="rounded-lg border border-slate-800 bg-slate-900/80 p-4">
                <h3 class="text-sm font-semibold text-white">Supervisores en turno</h3>
                <ul class="mt-3 space-y-2">
                    @forelse ($map['live'] as $row)
                        <li class="text-sm text-slate-300">
                            {{ $row['user'] ?? 'Supervisor' }}
                            @if (! empty($row['parked']['minutes']))
                                · parado {{ $row['parked']['minutes'] }} min
                            @elseif ($row['lat'])
                                · en ruta
                            @else
                                · sin GPS aún
                            @endif
                        </li>
                    @empty
                        <li class="text-sm text-slate-500">Nadie en turno ahora.</li>
                    @endforelse
                </ul>
            </section>
        @endif

        @if ($activeTab === 'history')
            <section class="rounded-lg border border-slate-800 bg-slate-900/80 p-4">
                <h3 class="text-sm font-semibold text-white">Rutas del periodo</h3>
                <p class="text-xs text-slate-500 mt-1">Una ruta a la vez. El trazo une pings GPS (no callejero de Google).</p>
                <div class="mt-3 flex flex-wrap items-center gap-3 hidden" id="supervision-replay-wrap">
                    <label class="text-xs text-slate-500" for="supervision-replay">Replay</label>
                    <input id="supervision-replay" type="range" min="0" value="0" class="flex-1 accent-amber-400">
                    <x-ui.button type="button" size="sm" variant="secondary" id="supervision-replay-play">Reproducir</x-ui.button>
                </div>
                <ul class="mt-3 space-y-1 max-h-64 overflow-y-auto">
                    @forelse ($map['history'] as $row)
                        <li>
                            <button type="button"
                                    class="supervision-trail-pick w-full text-left text-sm text-slate-300 rounded-md px-2 py-1.5 hover:bg-slate-800"
                                    data-shift-id="{{ $row['shift_id'] }}">
                                {{ $row['user'] ?? 'Supervisor' }} · {{ $row['status'] === 'open' ? 'abierto' : 'cerrado' }}
                                · {{ \Illuminate\Support\Carbon::parse($row['started_at'])->format('d/m H:i') }}
                                @if ($row['km_traveled'])
                                    · {{ $row['km_traveled'] }} km
                                @endif
                            </button>
                        </li>
                    @empty
                        <li class="text-sm text-slate-500">Sin turnos en el rango.</li>
                    @endforelse
                </ul>
            </section>
        @endif

        @if ($activeTab === 'summary')
            @include('modules.company.supervision.partials.summary-dashboard')
        @endif
    </div>

    @push('scripts')
        <script>
            (function () {
                const yearEl = document.getElementById('period-year');
                const monthEl = document.getElementById('period-month');
                const fromEl = document.getElementById('from');
                const toEl = document.getElementById('to');
                if (!yearEl || !monthEl || !fromEl || !toEl) return;

                const pad = (n) => String(n).padStart(2, '0');
                const lastDay = (y, m) => new Date(y, m, 0).getDate();
                const iso = (y, m, d) => `${y}-${pad(m)}-${pad(d)}`;

                function applyYearMonth() {
                    const y = Number(yearEl.value);
                    const m = monthEl.value;
                    if (m) {
                        fromEl.value = iso(y, Number(m), 1);
                        toEl.value = iso(y, Number(m), lastDay(y, Number(m)));
                        return;
                    }
                    fromEl.value = iso(y, 1, 1);
                    toEl.value = iso(y, 12, 31);
                }

                function syncSelectsFromDates() {
                    const from = fromEl.value;
                    const to = toEl.value;
                    if (!from || !to) return;
                    yearEl.value = from.slice(0, 4);
                    const sameMonth = from.slice(0, 7) === to.slice(0, 7)
                        && from.endsWith('-01')
                        && Number(to.slice(8, 10)) === lastDay(Number(from.slice(0, 4)), Number(from.slice(5, 7)));
                    const wholeYear = from.endsWith('-01-01') && to.endsWith('-12-31') && from.slice(0, 4) === to.slice(0, 4);
                    monthEl.value = sameMonth ? from.slice(5, 7) : (wholeYear ? '' : '');
                    if (!sameMonth && !wholeYear) {
                        monthEl.value = '';
                    }
                }

                yearEl.addEventListener('change', applyYearMonth);
                monthEl.addEventListener('change', applyYearMonth);
                document.querySelectorAll('[data-preset]').forEach((btn) => {
                    btn.addEventListener('click', () => {
                        const now = new Date();
                        const y = now.getFullYear();
                        const m = now.getMonth() + 1;
                        const d = now.getDate();
                        yearEl.value = String(y);
                        if (btn.dataset.preset === 'today') {
                            monthEl.value = pad(m);
                            fromEl.value = iso(y, m, d);
                            toEl.value = iso(y, m, d);
                            return;
                        }
                        if (btn.dataset.preset === 'month') {
                            monthEl.value = pad(m);
                            applyYearMonth();
                            return;
                        }
                        monthEl.value = '';
                        applyYearMonth();
                    });
                });
                syncSelectsFromDates();
            })();
        </script>
        <script>
            (function () {
                const live = {!! $liveJson !!};
                const history = {!! $historyJson !!};
                const reviews = {!! $reviewsJson !!};
                const clients = {!! $clientsJson !!};
                const googleMaps = {!! $googleMapsJson !!};
                const activeTab = @json($activeTab);
                const mapEl = document.getElementById('supervision-map');
                const fallback = document.getElementById('supervision-map-fallback');

                if (! mapEl) {
                    return;
                }

                const svgIcon = (svg, size, anchor) => ({
                    url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg),
                    scaledSize: new google.maps.Size(size, size),
                    anchor: new google.maps.Point(anchor, anchor),
                });

                const iconStart = () => svgIcon(
                    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><circle cx="16" cy="16" r="14" fill="#16a34a" stroke="#052e16" stroke-width="2"/><circle cx="16" cy="16" r="5" fill="#ecfdf5"/></svg>',
                    22,
                    11,
                );
                const iconMoto = () => ({
                    url: @json(asset('images/ui/supervisor-moto.png')),
                    scaledSize: new google.maps.Size(56, 40),
                    anchor: new google.maps.Point(28, 38),
                });
                const iconFlag = () => svgIcon(
                    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path fill="#111827" d="M8 4h3v24H8z"/><path fill="#dc2626" d="M11 5h16l-4 6 4 6H11z"/></svg>',
                    28,
                    4,
                );

                function circleIcon(color, scale) {
                    return {
                        path: google.maps.SymbolPath.CIRCLE,
                        scale: scale || 7,
                        fillColor: color,
                        fillOpacity: 1,
                        strokeColor: '#0b1220',
                        strokeWeight: 1,
                    };
                }

                let googleMap = null;
                let overlays = [];
                let replayMarker = null;
                let replayTimer = null;
                let replayPath = [];

                function clearOverlays() {
                    overlays.forEach((item) => item.setMap(null));
                    overlays = [];
                    if (replayMarker) {
                        replayMarker.setMap(null);
                        replayMarker = null;
                    }
                    clearInterval(replayTimer);
                }

                function addOverlay(item) {
                    overlays.push(item);
                    return item;
                }

                function extend(bounds, pos) {
                    if (pos?.lat == null || pos?.lng == null) return false;
                    bounds.extend(pos);
                    return true;
                }

                function drawClients(map, bounds) {
                    let any = false;
                    clients.forEach((row) => {
                        const pos = { lat: row.lat, lng: row.lng };
                        addOverlay(new google.maps.Marker({
                            map,
                            position: pos,
                            title: row.name,
                            zIndex: 1,
                            icon: circleIcon('#6366f1', 8),
                        }));
                        addOverlay(new google.maps.Marker({
                            map,
                            position: pos,
                            clickable: false,
                            zIndex: 1,
                            icon: { path: 'M0 0', scale: 0, labelOrigin: new google.maps.Point(0, -16) },
                            label: { text: row.name.length > 18 ? row.name.slice(0, 16) + '…' : row.name, color: '#e2e8f0', fontSize: '11px', fontWeight: '600' },
                        }));
                        any = extend(bounds, pos) || any;
                    });
                    return any;
                }

                function drawReviews(map, bounds, shiftId) {
                    let any = false;
                    reviews.forEach((row) => {
                        if (shiftId && Number(row.shift_id) !== Number(shiftId)) return;
                        const pos = { lat: row.lat, lng: row.lng };
                        addOverlay(new google.maps.Marker({
                            map,
                            position: pos,
                            zIndex: 3,
                            title: (row.user || 'Revista') + (row.client ? ' · ' + row.client : '') + (row.novelty ? ' · novedad' : ''),
                            icon: circleIcon(row.novelty ? '#f87171' : '#34d399', 6),
                        }));
                        any = extend(bounds, pos) || any;
                    });
                    return any;
                }

                function drawTrail(map, bounds, row, opts) {
                    let any = false;
                    const path = (row.path || []).map((p) => ({ lat: p.lat, lng: p.lng }));
                    if (path.length > 1) {
                        addOverlay(new google.maps.Polyline({
                            map,
                            path,
                            strokeColor: '#f59e0b',
                            strokeOpacity: 0.9,
                            strokeWeight: 4,
                        }));
                        path.forEach((p) => { any = extend(bounds, p) || any; });
                    }
                    if (row.start) {
                        addOverlay(new google.maps.Marker({
                            map,
                            position: { lat: row.start.lat, lng: row.start.lng },
                            title: (row.user || 'Inicio') + ' · inicio',
                            zIndex: 5,
                            icon: iconStart(),
                        }));
                        any = extend(bounds, row.start) || any;
                    }
                    (row.stops || []).forEach((stop) => {
                        addOverlay(new google.maps.Marker({
                            map,
                            position: { lat: stop.lat, lng: stop.lng },
                            title: 'Parado ' + stop.minutes + ' min',
                            zIndex: 4,
                            icon: circleIcon('#f59e0b', 8),
                            label: { text: String(stop.minutes) + '’', color: '#0f172a', fontSize: '10px', fontWeight: '700' },
                        }));
                        any = extend(bounds, stop) || any;
                    });
                    if (opts.current && row.end) {
                        addOverlay(new google.maps.Marker({
                            map,
                            position: { lat: row.end.lat, lng: row.end.lng },
                            title: (row.user || 'Supervisor') + (row.parked ? ' · parado ' + row.parked.minutes + ' min' : ''),
                            zIndex: 6,
                            icon: iconMoto(),
                        }));
                        any = extend(bounds, row.end) || any;
                    }
                    if (opts.flag && row.end) {
                        addOverlay(new google.maps.Marker({
                            map,
                            position: { lat: row.end.lat, lng: row.end.lng },
                            title: (row.user || 'Supervisor') + ' · cierre',
                            zIndex: 6,
                            icon: iconFlag(),
                        }));
                        any = extend(bounds, row.end) || any;
                    }
                    return any;
                }

                function paintLive() {
                    clearOverlays();
                    const bounds = new google.maps.LatLngBounds();
                    let hasPoint = drawClients(googleMap, bounds);
                    live.forEach((row) => {
                        hasPoint = drawTrail(googleMap, bounds, row, { current: true, flag: false }) || hasPoint;
                        hasPoint = drawReviews(googleMap, bounds, row.shift_id) || hasPoint;
                    });
                    if (hasPoint) googleMap.fitBounds(bounds, 48);
                }

                function paintHistory(shiftId) {
                    clearOverlays();
                    const bounds = new google.maps.LatLngBounds();
                    let hasPoint = drawClients(googleMap, bounds);
                    const row = history.find((item) => Number(item.shift_id) === Number(shiftId)) || history[0];
                    if (row) {
                        const closed = row.status !== 'open';
                        hasPoint = drawTrail(googleMap, bounds, row, { current: !closed, flag: closed }) || hasPoint;
                        hasPoint = drawReviews(googleMap, bounds, row.shift_id) || hasPoint;
                        setupReplay(row.path || []);
                    }
                    if (hasPoint) googleMap.fitBounds(bounds, 48);
                    document.querySelectorAll('.supervision-trail-pick').forEach((btn) => {
                        btn.classList.toggle('bg-slate-800', Number(btn.dataset.shiftId) === Number(row?.shift_id));
                        btn.classList.toggle('text-white', Number(btn.dataset.shiftId) === Number(row?.shift_id));
                    });
                }

                function setupReplay(path) {
                    replayPath = path || [];
                    const wrap = document.getElementById('supervision-replay-wrap');
                    const slider = document.getElementById('supervision-replay');
                    if (!wrap || !slider) return;
                    wrap.classList.toggle('hidden', replayPath.length < 2);
                    slider.max = Math.max(0, replayPath.length - 1);
                    slider.value = 0;
                    if (replayMarker) replayMarker.setMap(null);
                    replayMarker = null;
                    if (!replayPath.length) return;
                    replayMarker = new google.maps.Marker({
                        map: googleMap,
                        position: { lat: replayPath[0].lat, lng: replayPath[0].lng },
                        zIndex: 8,
                        icon: iconMoto(),
                        title: 'Replay',
                    });
                    slider.oninput = () => {
                        const point = replayPath[Number(slider.value)] || replayPath[0];
                        replayMarker.setPosition({ lat: point.lat, lng: point.lng });
                    };
                }

                function syncMapTypeButtons(type) {
                    document.querySelectorAll('.supervision-map-type-btn').forEach((btn) => {
                        const active = btn.dataset.mapType === type;
                        btn.classList.toggle('bg-indigo-600/80', active);
                        btn.classList.toggle('text-white', active);
                        btn.classList.toggle('text-slate-400', !active);
                    });
                }

                window.initSupervisionMap = function () {
                    if (!mapEl || !window.google?.maps) {
                        fallback?.classList.remove('hidden');
                        return;
                    }
                    const center = googleMaps.center || { lat: 4.57, lng: -74.29 };
                    googleMap = new google.maps.Map(mapEl, {
                        center,
                        zoom: googleMaps.zoom || 12,
                        mapTypeId: google.maps.MapTypeId.SATELLITE,
                        mapTypeControl: false,
                        streetViewControl: false,
                    });
                    syncMapTypeButtons('satellite');
                    document.querySelectorAll('.supervision-map-type-btn').forEach((btn) => {
                        btn.addEventListener('click', () => {
                            const type = btn.dataset.mapType || 'satellite';
                            googleMap.setMapTypeId(type === 'terrain'
                                ? google.maps.MapTypeId.TERRAIN
                                : google.maps.MapTypeId.SATELLITE);
                            syncMapTypeButtons(type);
                        });
                    });

                    if (activeTab === 'history') {
                        paintHistory(history[0]?.shift_id);
                        document.querySelectorAll('.supervision-trail-pick').forEach((btn) => {
                            btn.addEventListener('click', () => paintHistory(btn.dataset.shiftId));
                        });
                        document.getElementById('supervision-replay-play')?.addEventListener('click', () => {
                            if (!replayPath.length || !replayMarker) return;
                            const slider = document.getElementById('supervision-replay');
                            let i = 0;
                            clearInterval(replayTimer);
                            replayTimer = setInterval(() => {
                                const point = replayPath[i] || replayPath[0];
                                replayMarker.setPosition({ lat: point.lat, lng: point.lng });
                                if (slider) slider.value = String(i);
                                i += 1;
                                if (i >= replayPath.length) clearInterval(replayTimer);
                            }, 400);
                        });
                        return;
                    }

                    paintLive();
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
