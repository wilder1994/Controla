<x-client-layout title="Observatorio">
    <x-slot:headerTabs>
        @include('modules.observatory.partials.nav-tabs', [
            'indexRoute' => 'client.observatory.events.index',
            'typesRoute' => 'client.observatory.types.index',
            'vista' => $vista ?? 'tablero',
        ])
    </x-slot:headerTabs>

    <x-slot:actions>
        <a href="{{ route('client.observatory.reports.create') }}"
           class="inline-flex h-9 items-center rounded-lg bg-teal-600 px-4 text-sm font-medium text-white hover:bg-teal-500">
            Nuevo reporte
        </a>
    </x-slot:actions>

    @include('modules.observatory.partials.index-workspace', [
        'action' => route('client.observatory.events.index'),
        'search' => $search,
        'from' => $from,
        'to' => $to,
        'status' => $status,
        'vista' => $vista ?? 'tablero',
        'board' => $board,
        'map' => $map,
        'events' => $events,
        'publicUrl' => $publicUrl,
        'showClientColumn' => false,
        'eventShowRoute' => 'client.observatory.events.show',
        'accent' => 'teal',
    ])
</x-client-layout>
