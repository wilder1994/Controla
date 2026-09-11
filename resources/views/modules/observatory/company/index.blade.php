<x-company-layout title="Observatorio">
    @include('modules.observatory.partials.index-workspace', [
        'action' => route('company.observatory.events.index'),
        'search' => $search,
        'from' => $from,
        'to' => $to,
        'status' => $status,
        'board' => $board,
        'map' => $map,
        'events' => $events,
        'shareClients' => $shareClients,
        'showClientColumn' => true,
        'eventShowRoute' => 'company.observatory.events.show',
        'accent' => 'indigo',
    ])
</x-company-layout>
