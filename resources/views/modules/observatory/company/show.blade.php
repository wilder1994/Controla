<x-company-layout :title="$event->folio()">
    <x-slot:actions>
        <x-ui.button variant="secondary" :href="route('company.observatory.events.index', ['vista' => 'eventos'])" size="sm">← Eventos</x-ui.button>
    </x-slot:actions>
    @include('modules.observatory.partials.event-card', [
        'event' => $event,
        'canUpdateStatus' => false,
        'folioMap' => $folioMap ?? null,
    ])
</x-company-layout>
