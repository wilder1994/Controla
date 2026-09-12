<x-client-layout :title="$event->folio()">
    <a href="{{ route('client.observatory.events.index', ['vista' => 'eventos']) }}" class="text-sm text-slate-400 hover:text-white">← Eventos</a>
    <div class="mt-4">
        @include('modules.observatory.partials.event-card', [
            'event' => $event,
            'canUpdateStatus' => $canUpdateStatus,
            'canMerge' => $canMerge ?? false,
            'canDetach' => $canDetach ?? false,
            'statuses' => $statuses,
            'statusAction' => route('client.observatory.events.status', $event),
            'mergeAction' => route('client.observatory.events.merge', $event),
            'mergeCandidates' => $mergeCandidates ?? collect(),
            'folioMap' => $folioMap ?? null,
        ])
    </div>
</x-client-layout>
