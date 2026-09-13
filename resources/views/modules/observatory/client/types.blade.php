<x-client-layout title="Tipos del Observatorio">
    <x-slot:headerTabs>
        @include('modules.observatory.partials.nav-tabs', [
            'indexRoute' => 'client.observatory.events.index',
            'typesRoute' => 'client.observatory.types.index',
            'vista' => 'tipos',
        ])
    </x-slot:headerTabs>

    @include('modules.observatory.partials.types-catalog', [
        'types' => $types,
        'nextColor' => $nextColor,
        'canEdit' => $canEdit,
        'storeRoute' => 'client.observatory.types.store',
        'updateRoute' => 'client.observatory.types.update',
        'destroyRoute' => 'client.observatory.types.destroy',
        'accent' => 'teal',
    ])
</x-client-layout>
