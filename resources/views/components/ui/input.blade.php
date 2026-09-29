@props(['disabled' => false, 'accent' => 'indigo'])

@php
    $isPassword = ($attributes->get('type') ?? 'text') === 'password';
    $class = 'w-full h-9 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white placeholder:text-slate-600 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/30 disabled:opacity-50';
    if ($isPassword) {
        $class .= ' pr-10';
    }
@endphp

@if ($isPassword)
    <x-ui.password-wrap :disabled="$disabled" {{ $attributes->except('type')->merge(['class' => $class]) }} />
@else
    <input @disabled($disabled) {{ $attributes->merge(['class' => $class]) }}>
@endif
