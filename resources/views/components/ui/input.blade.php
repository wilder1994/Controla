@props(['disabled' => false, 'accent' => 'indigo'])

@php
    $focusClasses = match ($accent) {
        'platform' => 'focus:border-violet-500 focus:ring-violet-500/30',
        'client' => 'focus:border-teal-500 focus:ring-teal-500/30',
        default => 'focus:border-indigo-500 focus:ring-indigo-500/30',
    };
@endphp

<input @disabled($disabled) {{ $attributes->merge([
    'class' => "w-full h-9 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white placeholder:text-slate-600 focus:ring-1 disabled:opacity-50 {$focusClasses}",
]) }}>
