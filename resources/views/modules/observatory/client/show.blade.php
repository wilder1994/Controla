<x-client-layout :title="$event->folio()">
    <a href="{{ route('client.observatory.events.index') }}" class="text-sm text-slate-400 hover:text-white">← Observatorio</a>
    <div class="mt-4">
        @include('modules.observatory.partials.event-card', [
            'event' => $event,
            'canUpdateStatus' => $canUpdateStatus,
            'statuses' => $statuses,
            'statusAction' => route('client.observatory.events.status', $event),
        ])
    </div>
</x-client-layout>
