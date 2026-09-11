@php
    $vista = $vista ?? 'sitio';
    $accent = $accent ?? 'indigo';
    $editLink = $accent === 'indigo' ? 'text-indigo-300' : 'text-amber-300';
    $emptyHint = 'Sin puestos. Sin ellos la app no puede guardar revista.';
@endphp

<ul class="space-y-2">
    @forelse ($installation->supervisorPosts as $post)
        <li class="rounded-md border border-slate-800 px-3 py-2" x-data="{ editingPost: false }">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <p class="text-sm text-slate-200">
                    {{ $post->name }}
                    <span class="text-[10px] text-slate-400">{{ $post->modality?->label() }}</span>
                    @if ($post->employees->isNotEmpty())
                        <span class="text-[10px] text-slate-500">· {{ $post->employees->map->fullName()->join(', ') }}</span>
                    @endif
                    <span class="text-[10px] {{ $post->is_active ? 'text-emerald-400' : 'text-rose-400' }}">{{ $post->is_active ? 'activo' : 'inactivo' }}</span>
                </p>
                @if ($canManageTree)
                    <div class="flex items-center gap-2">
                        <button type="button" @click="editingPost = !editingPost" class="text-xs {{ $editLink }}">Editar</button>
                        <form method="POST" action="{{ route('company.clients.posts.destroy', [$client, $post]) }}" onsubmit="return confirm('¿Eliminar este puesto?')">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="vista" value="{{ $vista }}">
                            <button type="submit" class="text-xs text-rose-400">Eliminar</button>
                        </form>
                    </div>
                @endif
            </div>
            @if ($canManageTree)
                <div x-show="editingPost" x-cloak>
                    @include('modules.company.clients.partials.post-form', [
                        'client' => $client,
                        'post' => $post,
                        'vista' => $vista,
                        'accent' => $accent,
                        'installations' => $installations,
                        'postModalities' => $postModalities,
                    ])
                </div>
            @endif
        </li>
    @empty
        <li class="text-xs text-slate-500">{{ $emptyHint }}</li>
    @endforelse
</ul>

@if ($canManageTree)
    @include('modules.company.clients.partials.post-form', [
        'client' => $client,
        'installation' => $installation,
        'vista' => $vista,
        'accent' => $accent,
        'postModalities' => $postModalities,
    ])
@endif
