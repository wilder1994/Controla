@php
    $liveJson = json_encode($map['live'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    $historyJson = json_encode($map['history'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    $reviewsJson = json_encode($map['reviews'] ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    $googleMapsJson = json_encode($map['google_maps'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    $activeTab = in_array($tab ?? '', ['live', 'history', 'summary'], true) ? $tab : 'live';
    $tabQuery = array_filter([
        'from' => $summary->from,
        'to' => $summary->to,
        'zone_id' => $filter->zoneId ?? null,
        'supervisor_id' => $filter->supervisorId ?? null,
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
    </x-slot:headerTabs>

    <div class="space-y-4">
        @if ($activeTab !== 'summary')
            <div id="supervision-map" class="w-full h-[420px] rounded-lg border border-slate-800 bg-slate-950/60 overflow-hidden relative">
                <div id="supervision-map-fallback" class="absolute inset-0 flex items-center justify-center text-center p-6 text-sm text-slate-500 hidden">
                    <div>
                        <p class="text-slate-300 font-medium mb-1">Mapa no disponible</p>
                        <p>Configura <code class="text-indigo-300">GOOGLE_MAPS_API_KEY</code>.</p>
                    </div>
                </div>
            </div>
        @endif

        @if ($activeTab === 'live')
            <section class="rounded-lg border border-slate-800 bg-slate-900/80 p-4">
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
        @endif

        @if ($activeTab === 'history')
            <section
                class="rounded-lg border border-slate-800 bg-slate-900/80 p-4"
                x-data="{ replayIndex: 0, replayPath: {{ json_encode($map['history'][0]['path'] ?? []) }} }"
            >
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
                const googleMaps = {!! $googleMapsJson !!};
                const mapEl = document.getElementById('supervision-map');
                const fallback = document.getElementById('supervision-map-fallback');

                if (! mapEl) {
                    return;
                }

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

                    reviews.forEach((row) => {
                        if (row.lat == null || row.lng == null) return;
                        const pos = { lat: row.lat, lng: row.lng };
                        new google.maps.Marker({
                            map,
                            position: pos,
                            title: (row.user || 'Revista') + (row.client ? ' · ' + row.client : '') + (row.post ? ' · ' + row.post : '') + (row.novelty ? ' · novedad' : ''),
                            icon: {
                                path: google.maps.SymbolPath.CIRCLE,
                                scale: 7,
                                fillColor: row.novelty ? '#f87171' : '#34d399',
                                fillOpacity: 1,
                                strokeColor: '#0b1220',
                                strokeWeight: 1,
                            },
                        });
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
