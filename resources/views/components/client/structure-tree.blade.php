@props(['nodes', 'census', 'depth' => 0])

<ul class="{{ $depth === 0 ? 'space-y-2' : 'mt-2 ml-4 space-y-2 border-l border-slate-800 pl-4' }}">
    @foreach ($nodes as $node)
        @php
            $counts = $census[$node->id] ?? ['members' => 0, 'vehicles' => 0, 'pets' => 0];
        @endphp
        <li class="rounded-lg border border-slate-800 bg-slate-900/60 p-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <a href="{{ route('client.structures.show', $node) }}" class="font-medium text-white hover:text-teal-300">
                        {{ $node->name }}
                    </a>
                    <p class="text-xs text-slate-500">{{ $node->type->label() }} @if($node->code)<span class="font-mono">· {{ $node->code }}</span>@endif</p>
                </div>
                <div class="flex flex-wrap gap-2 text-xs">
                    <span class="rounded-full bg-indigo-900/50 px-2 py-0.5 text-indigo-200" title="Personas">{{ $counts['members'] }} personas</span>
                    <span class="rounded-full bg-sky-900/50 px-2 py-0.5 text-sky-200" title="Vehículos">{{ $counts['vehicles'] }} vehículos</span>
                    <span class="rounded-full bg-amber-900/50 px-2 py-0.5 text-amber-200" title="Mascotas">{{ $counts['pets'] }} mascotas</span>
                </div>
            </div>
            @if ($node->children->isNotEmpty())
                <x-client.structure-tree :nodes="$node->children" :census="$census" :depth="$depth + 1" />
            @endif
        </li>
    @endforeach
</ul>
