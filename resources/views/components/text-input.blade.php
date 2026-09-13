@props(['disabled' => false])

@php
    $isPassword = ($attributes->get('type') ?? 'text') === 'password';
    $class = 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm';
    if ($isPassword) {
        $class .= ' pr-10';
    }
@endphp

@if ($isPassword)
    <x-ui.password-wrap :disabled="$disabled" {{ $attributes->except('type')->merge(['class' => $class]) }} />
@else
    <input @disabled($disabled) {{ $attributes->merge(['class' => $class]) }}>
@endif
