<x-company-layout title="Observatorio">
    <x-slot:headerTabs>
        @include('modules.observatory.partials.nav-tabs', [
            'indexRoute' => 'company.observatory.events.index',
            'typesRoute' => 'company.observatory.types.index',
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
        'exportRoute' => route('company.observatory.board.export'),
        'accent' => 'indigo',
        'observatoryLiveUrl' => route('company.observatory.live', request()->query()),
    ])
</x-company-layout>
