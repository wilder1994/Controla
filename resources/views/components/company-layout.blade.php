@props(['title' => null])

<x-company-layout :title="$title">
    {{ $slot }}
</x-company-layout>
