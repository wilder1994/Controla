@php
    $canUpdateStatus = $canUpdateStatus ?? false;
    $canMerge = $canMerge ?? false;
    $canDetach = $canDetach ?? false;
    $statuses = $statuses ?? [];
    $statusAction = $statusAction ?? null;
    $mergeAction = $mergeAction ?? null;
    $mergeCandidates = $mergeCandidates ?? collect();
@endphp
<div class="space-y-4">
    <div class="rounded-lg border border-slate-800 bg-slate-900/80 p-4 space-y-2">
        <p class="font-mono text-xs text-indigo-300">{{ $event->folio() }}</p>
        <h3 class="text-lg font-semibold text-white">{{ $event->installation?->name }}</h3>
        <p class="text-xs text-slate-500">{{ $event->client?->name }} · {{ $event->statusLabel() }}</p>
        @if ($event->installation?->addressLine())
            <p class="text-xs text-slate-400">{{ $event->installation->addressLine() }}</p>
        @endif
        <p class="text-sm text-slate-300">{{ $event->title }}</p>
        <p class="text-xs text-slate-500">Los estados los cierra el admin de instalaciones de esa sede.</p>
    </div>

    @if ($canUpdateStatus && $statusAction)
        <form method="POST" action="{{ $statusAction }}" class="rounded-lg border border-slate-800 bg-slate-900/80 p-4 space-y-3">
            @csrf
            @method('PATCH')
            <p class="text-xs text-slate-500">Nuevo → En atención → Cerrado. No se reabre.</p>
            <div class="flex flex-wrap gap-2">
                @foreach ($statuses as $value => $label)
                    @php
                        $next = \App\Enums\ObservatoryEventStatus::tryFrom($value);
                        $canMove = $next instanceof \App\Enums\ObservatoryEventStatus && $event->canMoveTo($next);
                    @endphp
                    <button
                        type="submit"
                        name="status"
                        value="{{ $value }}"
                        @disabled(! $canMove)
                        class="h-9 px-3 rounded-lg text-xs font-semibold {{ $event->status->value === $value ? 'bg-indigo-600 text-white' : 'border border-slate-700 text-slate-200 hover:bg-slate-800 disabled:opacity-40' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
            <x-ui.field-error :messages="$errors->get('status')" />
        </form>
    @endif

    @if ($canMerge && $mergeAction && $mergeCandidates->isNotEmpty())
        <form method="POST" action="{{ $mergeAction }}" class="rounded-lg border border-slate-800 bg-slate-900/80 p-4 space-y-3">
            @csrf
            <p class="text-sm font-medium text-white">Unir folio</p>
            <p class="text-xs text-slate-500">Trae los reportes de otro evento de este colegio a este folio. El otro folio se elimina.</p>
            <select name="source_event_id" required class="w-full h-11 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white">
                <option value="">Elegir folio…</option>
                @foreach ($mergeCandidates as $candidate)
                    <option value="{{ $candidate->id }}">
                        {{ $candidate->folio() }} · {{ $candidate->title }} · {{ $candidate->statusLabel() }} · {{ $candidate->reports_count }} reporte{{ (int) $candidate->reports_count === 1 ? '' : 's' }}
                    </option>
                @endforeach
            </select>
            <x-ui.field-error :messages="$errors->get('source_event_id')" />
            <button type="submit" class="h-9 px-3 rounded-lg bg-indigo-600 text-xs font-semibold text-white">Unir aquí</button>
        </form>
    @endif

    <div class="rounded-lg border border-slate-800 bg-slate-900/80 p-4 space-y-3">
        <p class="text-sm font-medium text-white">Reportes</p>
        <x-ui.field-error :messages="$errors->get('report')" />
        @forelse ($event->reports as $report)
            <article class="rounded-lg border border-slate-800 bg-slate-950/50 p-3 space-y-1">
                <p class="text-xs text-slate-500">{{ $report->kindLabel() }} · {{ $report->sourceLabel() }} · {{ $report->created_at?->format('d/m/Y H:i') }}</p>
                <p class="text-sm text-slate-200 whitespace-pre-line">{{ $report->body }}</p>
                <p class="text-xs text-slate-500">{{ $report->reporterLabel() }}@if (! $report->is_anonymous && $report->reporter_phone) · {{ $report->reporter_phone }}@endif</p>
                @if ($report->hasCoordinates())
                    <p class="text-[11px] text-slate-500">Pin {{ number_format((float) $report->latitude, 5) }}, {{ number_format((float) $report->longitude, 5) }}</p>
                @endif
                @if ($report->photoUrl())
                    <img src="{{ $report->photoUrl() }}" alt="Evidencia" class="mt-2 max-h-64 rounded-lg border border-slate-800">
                @endif
                @if ($canDetach && $event->reports->count() > 1)
                    <form method="POST" action="{{ route('client.observatory.events.reports.detach', [$event, $report]) }}" class="pt-2">
                        @csrf
                        <button type="submit" class="h-8 px-2 rounded-md border border-slate-700 text-[11px] font-semibold text-slate-300 hover:bg-slate-800">
                            Sacar a folio nuevo
                        </button>
                    </form>
                @endif
            </article>
        @empty
            <p class="text-sm text-slate-500">Sin reportes.</p>
        @endforelse
    </div>

    @if ($event->statusLogs->isNotEmpty())
        <div class="rounded-lg border border-slate-800 bg-slate-900/80 p-4 space-y-2">
            <p class="text-sm font-medium text-white">Historial</p>
            @foreach ($event->statusLogs as $log)
                <p class="text-xs text-slate-400">
                    {{ $log->from_status?->label() }} → {{ $log->to_status?->label() }}
                    · {{ $log->user?->name ?? '—' }}
                    · {{ $log->created_at?->format('d/m/Y H:i') }}
                </p>
            @endforeach
        </div>
    @endif
</div>
