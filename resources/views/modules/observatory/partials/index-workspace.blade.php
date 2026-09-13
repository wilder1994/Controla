@php
    $action = $action ?? '';
    $search = $search ?? '';
    $from = $from ?? '';
    $to = $to ?? '';
    $status = $status ?? '';
    $vista = in_array($vista ?? 'tablero', ['tablero', 'eventos'], true) ? ($vista ?? 'tablero') : 'tablero';
    $grain = in_array($grain ?? 'day', ['day', 'month', 'year'], true) ? ($grain ?? 'day') : 'day';
    $filterClientId = $filterClientId ?? '';
    $filterClients = $filterClients ?? collect();
    $board = $board ?? [
        'total' => 0, 'nuevo' => 0, 'en_atencion' => 0, 'cerrado' => 0, 'closed_rate' => 0,
        'top' => [], 'trend' => ['labels' => ['—'], 'series' => []],
        'peaks' => ['labels' => ['—'], 'values' => [0]],
        'kinds' => ['labels' => [], 'values' => []], 'sources' => ['labels' => [], 'values' => []],
    ];
    $showClientColumn = $showClientColumn ?? false;
    $eventShowRoute = $eventShowRoute ?? 'client.observatory.events.show';
    $accent = $accent ?? 'teal';
    $linkClass = $accent === 'indigo' ? 'text-indigo-400 hover:text-indigo-300' : 'text-teal-400 hover:text-teal-300';
    $folioClass = $accent === 'indigo' ? 'text-indigo-300' : 'text-teal-300';
    $searchPlaceholder = $showClientColumn
        ? 'Buscar por sede, DANE, cliente o texto…'
        : 'Buscar por sede, DANE o texto…';
    $docsUrl = route('observatory.docs');
    $tabUrl = static function (string $tab, ?string $statusValue = null) use ($status): string {
        return request()->fullUrlWithQuery([
            'vista' => $tab,
            'status' => $tab === 'eventos' ? ($statusValue ?? $status) : ($statusValue ?? $status ?: null),
        ]);
    };
    $kpiUrl = static function (?string $value) use ($tabUrl): string {
        return $tabUrl('eventos', $value);
    };
    $charts = [
        'accent' => $accent,
        'closed_rate' => (int) ($board['closed_rate'] ?? 0),
        'trend' => $board['trend'] ?? ['labels' => ['—'], 'series' => []],
        'peaks' => $board['peaks'] ?? ['labels' => ['—'], 'values' => [0]],
        'sources' => $board['sources'] ?? ['labels' => [], 'values' => []],
    ];
@endphp

@push('styles')
<style>
    [x-cloak] { display: none !important; }
    .obs-board { --obs-line: {{ $accent === 'indigo' ? '#818cf8' : '#2dd4bf' }}; }
    .obs-kpi {
        position: relative;
        overflow: hidden;
        border-radius: 0.7rem;
        border: 1px solid rgb(30 41 59);
        background: linear-gradient(180deg, rgb(15 23 42 / 0.95), rgb(2 6 23 / 0.8));
        padding: 0.65rem 0.8rem 0.6rem;
        transition: border-color .15s ease;
    }
    .obs-kpi.is-on { border-color: rgb(245 158 11 / 0.45); }
    .obs-kpi::before {
        content: "";
        position: absolute; inset: 0 auto 0 0; width: 3px;
        background: var(--obs-tone, #64748b);
    }
    .obs-card {
        border-radius: 0.75rem;
        border: 1px solid rgb(30 41 59);
        background: linear-gradient(180deg, rgb(15 23 42 / 0.92), rgb(2 6 23 / 0.88));
        min-width: 0;
    }
    .obs-card-h { padding: 0.55rem 0.75rem 0; }
    .obs-card-h p { font-size: 10px; letter-spacing: .08em; text-transform: uppercase; color: #64748b; margin: 0; }
    .obs-card-h h3 { margin: .1rem 0 0; font-size: .85rem; font-weight: 600; color: #e2e8f0; }
    .obs-chart { height: 9.5rem; padding: .35rem .75rem .65rem; }
    .obs-chart-lg { height: 13.5rem; }
    .obs-gauge { height: 8.5rem; }
</style>
@endpush

<div class="obs-board space-y-3" @if ($vista === 'tablero') x-data="observatoryBoard(@js($charts))" @endif>
    <form method="GET" action="{{ $action }}"
          class="rounded-xl border border-slate-800 bg-slate-900/70 p-2.5 flex flex-col xl:flex-row xl:items-end gap-2">
        <input type="hidden" name="vista" value="{{ $vista }}">
        @if ($showClientColumn && $filterClients->isNotEmpty())
            <div class="min-w-[12rem]">
                <label for="client_id" class="block text-[11px] text-slate-500 mb-1">Cliente</label>
                <select id="client_id" name="client_id" class="w-full h-9 px-2 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white">
                    <option value="">Todos</option>
                    @foreach ($filterClients as $row)
                        <option value="{{ $row->id }}" @selected((string) $filterClientId === (string) $row->id)>{{ $row->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div>
            <label for="from" class="block text-[11px] text-slate-500 mb-1">Desde</label>
            <input type="date" id="from" name="from" value="{{ $from }}"
                   class="h-9 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white">
        </div>
        <div>
            <label for="to" class="block text-[11px] text-slate-500 mb-1">Hasta</label>
            <input type="date" id="to" name="to" value="{{ $to }}"
                   class="h-9 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white">
        </div>
        <div>
            <label for="grain" class="block text-[11px] text-slate-500 mb-1">Líneas</label>
            <select id="grain" name="grain" class="h-9 px-2 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white">
                <option value="day" @selected($grain === 'day')>Por día</option>
                <option value="month" @selected($grain === 'month')>Por mes</option>
                <option value="year" @selected($grain === 'year')>Por año</option>
            </select>
        </div>
        <div class="flex-1 min-w-0">
            <label for="q" class="block text-[11px] text-slate-500 mb-1">Buscar</label>
            <input type="search" id="q" name="q" value="{{ $search }}" placeholder="{{ $searchPlaceholder }}"
                   class="w-full h-9 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white placeholder:text-slate-600">
        </div>
        @if ($status !== '')
            <input type="hidden" name="status" value="{{ $status }}">
        @endif
        <button type="submit" class="h-9 px-4 rounded-lg border border-slate-700 text-sm text-slate-200 hover:bg-slate-800">Filtrar</button>
        @if ($vista === 'tablero')
            @include('modules.observatory.partials.share-modal')
        @endif
        <a href="{{ $docsUrl }}" target="_blank" rel="noopener"
           class="h-9 px-4 inline-flex items-center rounded-lg border border-slate-700 text-sm text-slate-300 hover:bg-slate-800">API</a>
    </form>

    @if ($vista === 'tablero')
        <div class="grid grid-cols-2 xl:grid-cols-4 gap-2">
            @foreach ([
                [null, 'Eventos', $board['total'], $status === '', '#94a3b8'],
                ['nuevo', 'Nuevos', $board['nuevo'], $status === 'nuevo', '#f59e0b'],
                ['en_atencion', 'En atención', $board['en_atencion'], $status === 'en_atencion', '#818cf8'],
                ['cerrado', 'Cerrados', $board['cerrado'], $status === 'cerrado', '#34d399'],
            ] as [$value, $label, $count, $on, $tone])
                <a href="{{ $kpiUrl($value) }}" class="obs-kpi {{ $on ? 'is-on' : '' }}" style="--obs-tone: {{ $tone }}">
                    <p class="text-[10px] uppercase tracking-wide text-slate-500">{{ $label }}</p>
                    <p class="mt-0.5 text-2xl font-semibold tabular-nums text-white">{{ $count }}</p>
                </a>
            @endforeach
        </div>

        <div class="grid gap-2 xl:grid-cols-[minmax(0,7fr)_minmax(13rem,3fr)] xl:items-stretch">
            @include('modules.observatory.partials.map', [
                'map' => $map,
                'mapCanvasClass' => 'h-64 xl:h-full xl:min-h-[20rem]',
            ])
            @include('modules.observatory.partials.pin-legend')
        </div>

        <div class="grid gap-2 xl:grid-cols-[minmax(14rem,1fr)_minmax(0,2fr)] xl:items-stretch">
            <section class="obs-card p-2.5 flex flex-col min-h-0">
                <p class="text-[10px] uppercase tracking-wide text-slate-500 shrink-0">Colegios por riesgo</p>
                <div class="mt-1.5 space-y-1.5 overflow-y-auto max-h-48 xl:max-h-none xl:flex-1 sidebar-scroll">
                    @forelse ($board['top'] as $row)
                        <div class="flex items-start justify-between gap-2 text-sm">
                            <div class="min-w-0">
                                <p class="text-slate-200 truncate text-[13px]">{{ $row['name'] }}</p>
                                @if ($showClientColumn && filled($row['client']))
                                    <p class="text-[10px] text-slate-500 truncate">{{ $row['client'] }}</p>
                                @endif
                            </div>
                            <p class="font-mono text-[11px] text-slate-300 shrink-0">{{ $row['score'] ?? $row['count'] }} <span class="text-slate-600">{{ $row['count'] }}</span></p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Aún no hay eventos en el periodo.</p>
                    @endforelse
                </div>
            </section>
            <section class="obs-card">
                <div class="obs-card-h">
                    <p>Tendencia por tipo</p>
                    <h3>{{ $grain === 'year' ? 'Meses del periodo' : ($grain === 'month' ? 'Días del mes' : 'Día a día') }}</h3>
                </div>
                <div class="obs-chart obs-chart-lg"><canvas x-ref="trend" aria-label="Tendencia por tipo"></canvas></div>
            </section>
        </div>

        <div class="grid gap-2 md:grid-cols-2 xl:grid-cols-[1.15fr_1.15fr_0.85fr] xl:items-stretch">
            <section class="obs-card">
                <div class="obs-card-h">
                    <p>Picos</p>
                    <h3>Días con más reportes</h3>
                </div>
                <div class="obs-chart"><canvas x-ref="peaks" aria-label="Días con más reportes"></canvas></div>
            </section>
            <section class="obs-card">
                <div class="obs-card-h">
                    <p>Canal</p>
                    <h3>De dónde llega</h3>
                </div>
                <div class="obs-chart"><canvas x-ref="sources" aria-label="Reportes por canal"></canvas></div>
            </section>
            <section class="obs-card">
                <div class="obs-card-h">
                    <p>Cierre</p>
                    <h3>Eventos resueltos</h3>
                </div>
                <div class="obs-chart obs-gauge relative">
                    <canvas x-ref="gauge" aria-label="Medidor de cierre"></canvas>
                    <div class="absolute inset-x-0 bottom-2 text-center pointer-events-none">
                        <p class="text-2xl font-semibold tabular-nums text-white">{{ (int) ($board['closed_rate'] ?? 0) }}%</p>
                        <p class="text-[10px] text-slate-500">{{ ((int) ($board['total'] ?? 0)) === 0 ? 'Sin eventos' : 'Cerrados / total' }}</p>
                    </div>
                </div>
            </section>
        </div>
    @else
        <div class="rounded-xl border border-slate-800 overflow-hidden bg-slate-900/80">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-950/60 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-2.5 text-left font-medium">Folio</th>
                        <th class="px-4 py-2.5 text-left font-medium">Sede</th>
                        @if ($showClientColumn)
                            <th class="px-4 py-2.5 text-left font-medium hidden md:table-cell">Cliente</th>
                        @endif
                        <th class="px-4 py-2.5 text-left font-medium hidden sm:table-cell">Tipo</th>
                        <th class="px-4 py-2.5 text-left font-medium">Estado</th>
                        <th class="px-4 py-2.5 text-left font-medium hidden lg:table-cell">Abierto</th>
                        <th class="px-4 py-2.5 text-right font-medium">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse ($events as $event)
                        <tr class="hover:bg-slate-800/30">
                            <td class="px-4 py-3 font-mono text-xs {{ $folioClass }}">{{ $event->folio() }}</td>
                            <td class="px-4 py-3 text-slate-200">{{ $event->installation?->name }}</td>
                            @if ($showClientColumn)
                                <td class="px-4 py-3 hidden md:table-cell text-slate-400">{{ $event->client?->name }}</td>
                            @endif
                            <td class="px-4 py-3 hidden sm:table-cell text-slate-300">{{ $event->kindLabel() }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ $event->statusLabel() }}</td>
                            <td class="px-4 py-3 hidden lg:table-cell text-slate-400 tabular-nums">{{ $event->opened_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route($eventShowRoute, $event) }}" class="text-xs {{ $linkClass }}">Ver</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $showClientColumn ? 7 : 6 }}" class="px-4 py-10 text-center text-sm text-slate-500">Aún no hay reportes.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($events->hasPages())
            <div>{{ $events->links() }}</div>
        @endif
    @endif
</div>
