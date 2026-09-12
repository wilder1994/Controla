<x-client-layout :title="$event->folio()">
    <a href="{{ route('client.observatory.events.index') }}" class="text-sm text-slate-400 hover:text-white">← Observatorio</a>
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
        ])
    </div>
</x-client-layout>
