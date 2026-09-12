<x-client-layout title="Observatorio">
    @if ($canReport ?? false)
        <div class="mb-4">
            <a href="{{ route('client.observatory.reports.create') }}" class="inline-flex h-10 items-center rounded-lg bg-teal-600 px-4 text-sm font-semibold text-white">Nuevo reporte</a>
        </div>
    @endif
    @include('modules.observatory.partials.index-workspace', [
        'action' => route('client.observatory.events.index'),
        'search' => $search,
        'from' => $from,
        'to' => $to,
        'status' => $status,
        'board' => $board,
        'map' => $map,
        'events' => $events,
        'publicUrl' => $publicUrl,
        'showClientColumn' => false,
        'eventShowRoute' => 'client.observatory.events.show',
        'accent' => 'teal',
    ])
</x-client-layout>
