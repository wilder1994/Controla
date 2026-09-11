@props(['structure'])

@if ($structure)
    <span class="text-slate-300">{{ $structure->name }}</span>
    @if ($structure->installation)
        <span class="block text-xs text-slate-500">{{ $structure->installation->name }}</span>
    @endif
@else
    <span class="text-slate-500">—</span>
@endif
