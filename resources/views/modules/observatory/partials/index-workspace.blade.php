@php
    $action = $action ?? '';
    $search = $search ?? '';
    $from = $from ?? '';
    $to = $to ?? '';
    $status = $status ?? '';
    $board = $board ?? [
        'total' => 0, 'nuevo' => 0, 'en_atencion' => 0, 'cerrado' => 0, 'closed_rate' => 0,
        'top' => [], 'trend' => ['labels' => ['—'], 'values' => [0]],
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
    $kpiUrl = static function (?string $value): string {
        return request()->fullUrlWithQuery(['status' => $value]);
    };
    $charts = [
        'accent' => $accent,
        'closed_rate' => (int) ($board['closed_rate'] ?? 0),
        'trend' => $board['trend'] ?? ['labels' => ['—'], 'values' => [0]],
        'kinds' => $board['kinds'] ?? ['labels' => [], 'values' => []],
        'sources' => $board['sources'] ?? ['labels' => [], 'values' => []],
    ];
@endphp

@push('styles')
<style>
    .obs-board { --obs-line: {{ $accent === 'indigo' ? '#818cf8' : '#2dd4bf' }}; }
    .obs-kpi {
        position: relative;
        overflow: hidden;
        border-radius: 0.85rem;
        border: 1px solid rgb(30 41 59);
        background: linear-gradient(180deg, rgb(15 23 42 / 0.95), rgb(2 6 23 / 0.8));
        padding: 0.9rem 1rem 0.85rem;
        transition: border-color .15s ease, transform .15s ease;
    }
    .obs-kpi:hover { border-color: rgb(51 65 85); transform: translateY(-1px); }
    .obs-kpi.is-on { border-color: rgb(245 158 11 / 0.45); box-shadow: 0 0 0 1px rgb(245 158 11 / 0.15); }
    .obs-kpi::before {
        content: "";
        position: absolute; inset: 0 auto 0 0; width: 3px;
        background: var(--obs-tone, #64748b);
    }
    .obs-card {
        border-radius: 0.9rem;
        border: 1px solid rgb(30 41 59);
        background: linear-gradient(180deg, rgb(15 23 42 / 0.92), rgb(2 6 23 / 0.88));
        min-width: 0;
    }
    .obs-card-h { padding: 0.85rem 1rem 0; }
    .obs-card-h p { font-size: 11px; letter-spacing: .08em; text-transform: uppercase; color: #64748b; margin: 0; }
    .obs-card-h h3 { margin: .15rem 0 0; font-size: .95rem; font-weight: 600; color: #e2e8f0; }
    .obs-chart { height: 13.5rem; padding: .5rem 1rem 1rem; }
    .obs-gauge { height: 12.5rem; }
</style>
@endpush

<div class="obs-board space-y-4" x-data="observatoryBoard(@js($charts))">
    <form method="GET" action="{{ $action }}"
          class="rounded-xl border border-slate-800 bg-slate-900/70 p-3 flex flex-col lg:flex-row lg:items-end gap-3">
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
        <div class="flex-1 min-w-0">
            <label for="q" class="block text-[11px] text-slate-500 mb-1">Buscar</label>
            <input type="search" id="q" name="q" value="{{ $search }}" placeholder="{{ $searchPlaceholder }}"
                   class="w-full h-9 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white placeholder:text-slate-600">
        </div>
        @if ($status !== '')
            <input type="hidden" name="status" value="{{ $status }}">
        @endif
        <button type="submit" class="h-9 px-4 rounded-lg border border-slate-700 text-sm text-slate-200 hover:bg-slate-800">Filtrar</button>
        <a href="{{ $docsUrl }}" target="_blank" rel="noopener"
           class="h-9 px-4 inline-flex items-center rounded-lg border border-slate-700 text-sm text-slate-300 hover:bg-slate-800">API</a>
    </form>

    <div class="grid grid-cols-2 xl:grid-cols-4 gap-3">
        @foreach ([
            [null, 'Eventos', $board['total'], $status === '', '#94a3b8'],
            ['nuevo', 'Nuevos', $board['nuevo'], $status === 'nuevo', '#f59e0b'],
            ['en_atencion', 'En atención', $board['en_atencion'], $status === 'en_atencion', '#818cf8'],
            ['cerrado', 'Cerrados', $board['cerrado'], $status === 'cerrado', '#34d399'],
        ] as [$value, $label, $count, $on, $tone])
            <a href="{{ $kpiUrl($value) }}" class="obs-kpi {{ $on ? 'is-on' : '' }}" style="--obs-tone: {{ $tone }}">
                <p class="text-[11px] uppercase tracking-wide text-slate-500">{{ $label }}</p>
                <p class="mt-1 text-3xl font-semibold tabular-nums text-white">{{ $count }}</p>
                <p class="mt-1 text-[11px] text-slate-500">{{ $on ? 'Filtro activo' : 'Clic para filtrar' }}</p>
            </a>
        @endforeach
    </div>

    <div class="grid gap-4 xl:grid-cols-[minmax(0,1.4fr)_minmax(16rem,0.7fr)]">
        <section class="obs-card">
            <div class="obs-card-h">
                <p>Tendencia</p>
                <h3>Eventos abiertos por día</h3>
            </div>
            <div class="obs-chart"><canvas x-ref="trend" aria-label="Tendencia de eventos"></canvas></div>
        </section>
        <section class="obs-card">
            <div class="obs-card-h">
                <p>Cierre</p>
                <h3>Eventos resueltos</h3>
            </div>
            <div class="obs-chart obs-gauge relative">
                <canvas x-ref="gauge" aria-label="Medidor de cierre"></canvas>
                <div class="absolute inset-x-0 bottom-3 text-center pointer-events-none">
                    <p class="text-3xl font-semibold tabular-nums text-white">{{ (int) ($board['closed_rate'] ?? 0) }}%</p>
                    <p class="text-[11px] text-slate-500">{{ ((int) ($board['total'] ?? 0)) === 0 ? 'Sin eventos en el periodo' : 'Cerrados / total' }}</p>
                </div>
            </div>
        </section>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <section class="obs-card">
            <div class="obs-card-h">
                <p>Tipo</p>
                <h3>Qué se reporta</h3>
            </div>
            <div class="obs-chart"><canvas x-ref="kinds" aria-label="Reportes por tipo"></canvas></div>
        </section>
        <section class="obs-card">
            <div class="obs-card-h">
                <p>Canal</p>
                <h3>De dónde llega</h3>
            </div>
            <div class="obs-chart"><canvas x-ref="sources" aria-label="Reportes por canal"></canvas></div>
        </section>
    </div>

    <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_20rem] xl:items-stretch">
        @include('modules.observatory.partials.map', ['map' => $map])

        <div class="space-y-3">
            <div class="obs-card p-3 space-y-2">
                <p class="text-xs uppercase tracking-wide text-slate-500">Colegios con más eventos</p>
                @forelse ($board['top'] as $row)
                    <div class="flex items-start justify-between gap-3 text-sm">
                        <div class="min-w-0">
                            <p class="text-slate-200 truncate">{{ $row['name'] }}</p>
                            @if ($showClientColumn && filled($row['client']))
                                <p class="text-[11px] text-slate-500 truncate">{{ $row['client'] }}</p>
                            @endif
                        </div>
                        <p class="font-mono text-xs text-slate-300 shrink-0">{{ $row['count'] }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Aún no hay eventos en el periodo.</p>
                @endforelse
            </div>

            <div class="obs-card p-3 space-y-2">
                <p class="text-xs uppercase tracking-wide text-slate-500">Link para reportar</p>
                @isset($publicUrl)
                    @include('modules.observatory.partials.public-link', ['url' => $publicUrl])
                @else
                    @forelse ($shareClients ?? [] as $shareClient)
                        @include('modules.observatory.partials.public-link', [
                            'url' => route('observatory.public.show', $shareClient->slug),
                            'name' => $shareClient->name,
                        ])
                    @empty
                        <p class="text-sm text-slate-500">No hay clientes activos con slug.</p>
                    @endforelse
                @endisset
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-slate-800 overflow-hidden bg-slate-900/80">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-950/60 text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-2.5 text-left font-medium">Folio</th>
                    <th class="px-4 py-2.5 text-left font-medium">Sede</th>
                    @if ($showClientColumn)
                        <th class="px-4 py-2.5 text-left font-medium hidden md:table-cell">Cliente</th>
                    @endif
                    <th class="px-4 py-2.5 text-left font-medium">Estado</th>
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
                        <td class="px-4 py-3 text-slate-300">{{ $event->statusLabel() }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route($eventShowRoute, $event) }}" class="text-xs {{ $linkClass }}">Ver</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $showClientColumn ? 5 : 4 }}" class="px-4 py-10 text-center text-sm text-slate-500">Aún no hay reportes.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($events->hasPages())
        <div>{{ $events->links() }}</div>
    @endif
</div>
