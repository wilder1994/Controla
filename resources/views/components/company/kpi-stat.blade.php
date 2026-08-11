@props([
    'label',
    'value',
    'tone' => 'neutral', // neutral | info | success | warning | danger
])

@php
    $valueClass = match ($tone) {
        'danger' => 'text-red-400',
        'warning' => 'text-amber-400',
        'success' => 'text-emerald-400',
        'info' => 'text-indigo-300',
        default => 'text-white',
    };
@endphp

<div class="rounded-lg border border-slate-800 bg-slate-900/80 px-3 py-2.5 company-kpi-stat">
    <p class="text-xs text-slate-500 leading-snug">{{ $label }}</p>
    <p class="mt-0.5 text-base font-semibold tabular-nums {{ $valueClass }}">{{ $value }}</p>
</div>
