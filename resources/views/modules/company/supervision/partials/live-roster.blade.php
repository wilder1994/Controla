@php
    $rows = $rows ?? [];
@endphp
@if ($rows === [])
    <p class="text-sm text-slate-500">Nadie en turno ahora.</p>
@else
    <table class="w-full text-sm">
        <thead>
            <tr class="text-[11px] uppercase tracking-wide text-slate-500 border-b border-slate-800">
                <th class="text-left py-2 pr-2 font-medium">Supervisor</th>
                <th class="text-left py-2 pr-2 font-medium">Estado</th>
                <th class="text-right py-2 font-medium">Km</th>
                <th class="text-right py-2 font-medium">Rev.</th>
                <th class="text-right py-2 font-medium"></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr class="supervision-live-pick border-b border-slate-800/70 align-top cursor-pointer hover:bg-slate-800/60"
                    data-shift-id="{{ $row['shift_id'] ?? '' }}">
                    <td class="py-2 pr-2">
                        <p class="font-medium text-slate-100">{{ $row['user'] ?? 'Supervisor' }}</p>
                        @php
                            $signal = $row['signal'] ?? (! empty($row['online']) ? 'online' : 'no_signal');
                            $signalClass = match ($signal) {
                                'screen_off' => 'text-amber-400',
                                'online' => 'text-emerald-400',
                                default => 'text-red-400',
                            };
                        @endphp
                        <p class="text-xs mt-0.5 {{ $signalClass }}">
                            {{ $row['online_label'] ?? 'Sin señal' }}
                        </p>
                        <p class="text-xs text-slate-500 mt-0.5">Inicio {{ $row['started_at_label'] ?? '—' }}</p>
                    </td>
                    <td class="py-2 pr-2 text-xs text-slate-300 leading-snug">{{ $row['status_line'] ?? '—' }}</td>
                    <td class="py-2 text-right text-slate-300 tabular-nums">{{ number_format((float) ($row['km'] ?? 0), 1) }}</td>
                    <td class="py-2 text-right text-slate-300 tabular-nums">{{ (int) ($row['reviews_count'] ?? 0) }}</td>
                    <td class="py-2 pl-2 text-right">
                        @if (! empty($row['sheet_url']))
                            <a href="{{ $row['sheet_url'] }}" target="_blank" rel="noopener"
                               class="inline-flex rounded-md border border-slate-700 px-2 py-0.5 text-[11px] font-semibold text-indigo-300 hover:bg-slate-800"
                               onclick="event.stopPropagation()">Ver</a>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
