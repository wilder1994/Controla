<x-client-layout title="Observatorio">
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
