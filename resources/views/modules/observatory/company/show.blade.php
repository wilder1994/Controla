<x-company-layout :title="$event->folio()">
    <x-slot:actions>
        <x-ui.button variant="secondary" :href="route('company.observatory.events.index')" size="sm">← Listado</x-ui.button>
    </x-slot:actions>
    @include('modules.observatory.partials.event-card', [
        'event' => $event,
        'canUpdateStatus' => false,
    ])
</x-company-layout>
