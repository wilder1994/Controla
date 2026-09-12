<x-company-layout title="Observatorio">
    <x-slot:headerTabs>
        @include('modules.observatory.partials.nav-tabs', [
            'indexRoute' => 'company.observatory.events.index',
            'vista' => $vista ?? 'tablero',
        ])
    </x-slot:headerTabs>

    @include('modules.observatory.partials.index-workspace', [
        'action' => route('company.observatory.events.index'),
        'search' => $search,
        'from' => $from,
        'to' => $to,
        'status' => $status,
        'vista' => $vista ?? 'tablero',
        'board' => $board,
        'map' => $map,
        'events' => $events,
        'shareClients' => $shareClients,
        'showClientColumn' => true,
        'eventShowRoute' => 'company.observatory.events.show',
        'accent' => 'indigo',
    ])
</x-company-layout>
