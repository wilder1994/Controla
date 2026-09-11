@php
    $action = $action ?? '';
    $search = $search ?? '';
    $from = $from ?? '';
    $to = $to ?? '';
    $status = $status ?? '';
    $board = $board ?? ['total' => 0, 'nuevo' => 0, 'en_atencion' => 0, 'cerrado' => 0, 'top' => []];
    $showClientColumn = $showClientColumn ?? false;
    $eventShowRoute = $eventShowRoute ?? 'client.observatory.events.show';
    $accent = $accent ?? 'teal';
    $linkClass = $accent === 'indigo' ? 'text-indigo-400 hover:text-indigo-300' : 'text-teal-400 hover:text-teal-300';
    $folioClass = $accent === 'indigo' ? 'text-indigo-300' : 'text-teal-300';
    $searchPlaceholder = $showClientColumn
        ? 'Buscar por sede, DANE, cliente o texto…'
        : 'Buscar por sede, DANE o texto…';

    $kpiUrl = static function (?string $value): string {
        return request()->fullUrlWithQuery(['status' => $value]);
    };
@endphp

<div class="space-y-4">
    <form method="GET" action="{{ $action }}"
          class="rounded-lg border border-slate-800 bg-slate-900/60 p-3 flex flex-col lg:flex-row lg:items-end gap-3">
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
    </form>

    <div class="grid grid-cols-2 xl:grid-cols-4 gap-3">
        @foreach ([
            [null, 'Eventos', $board['total'], $status === ''],
            ['nuevo', 'Nuevos', $board['nuevo'], $status === 'nuevo'],
            ['en_atencion', 'En atención', $board['en_atencion'], $status === 'en_atencion'],
            ['cerrado', 'Cerrados', $board['cerrado'], $status === 'cerrado'],
        ] as [$value, $label, $count, $on])
            <a href="{{ $kpiUrl($value) }}"
               class="rounded-lg border px-3 py-3 {{ $on ? 'border-amber-500/50 bg-amber-500/10' : 'border-slate-800 bg-slate-900/80 hover:border-slate-700' }}">
                <p class="text-[11px] uppercase tracking-wide text-slate-500">{{ $label }}</p>
                <p class="mt-1 text-2xl font-semibold text-white">{{ $count }}</p>
            </a>
        @endforeach
    </div>

    <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_20rem] xl:items-stretch">
        @include('modules.observatory.partials.map', ['map' => $map])

        <div class="space-y-3">
            <div class="rounded-lg border border-slate-800 bg-slate-900/80 p-3 space-y-2">
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

            <div class="rounded-lg border border-slate-800 bg-slate-900/60 p-3 space-y-2">
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

    <div class="rounded-lg border border-slate-800 overflow-hidden bg-slate-900/80">
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
