@props([
    'variant' => 'primary',
    'size' => 'sm',
    'href' => null,
    'type' => 'button',
])

@php
    $sizeClasses = match ($size) {
        'md' => 'h-10 px-5 text-sm font-semibold',
        default => 'h-9 px-4 text-sm font-medium',
    };

    $variantClasses = match ($variant) {
        'secondary' => 'ui-btn-secondary',
        'success' => 'ui-btn-success',
        'platform' => 'ui-btn-primary',
        default => 'ui-btn-primary',
    };

    $classes = "inline-flex items-center justify-center rounded-lg transition {$sizeClasses} {$variantClasses}";
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
