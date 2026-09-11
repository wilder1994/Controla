@php
    $canManageTree = $canManageTree ?? false;
    $installations = $installations ?? collect();
    $proReviews = $proReviews ?? collect();
@endphp

<section class="rounded-lg border border-slate-800 bg-slate-900/80 p-4 space-y-4">
    <div>
        <h3 class="text-sm font-semibold text-white">Instalaciones y puestos</h3>
        <p class="mt-1 text-xs text-slate-500">
            Las instalaciones son las mismas del mundo Accesos y todas llevan ubicación. Aquí se crean los puestos de Supervisión de campo.
        </p>
    </div>

    @if ($canManageTree)
        @include('modules.company.clients.partials.installation-form', ['client' => $client, 'vista' => 'supervision', 'accent' => 'amber'])
    @endif

    <div class="space-y-3">
        @forelse ($installations as $installation)
            <article class="rounded-lg border border-slate-800 bg-slate-950/40 p-3 space-y-3" x-data="{ editing: false }">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-sm font-medium text-white">{{ $installation->name }}</p>
                        @if ($installation->is_client_site)
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-900/40 text-amber-300">Mismo cliente</span>
                        @endif
                        <span class="text-xs text-slate-500">{{ $installation->supervisorPosts->count() }} puesto{{ $installation->supervisorPosts->count() === 1 ? '' : 's' }}</span>
                        @if ($installation->address || $installation->city)
                            <span class="text-xs text-slate-500">{{ $installation->address }}{{ $installation->city ? ' · '.$installation->city : '' }}</span>
                        @endif
                    </div>
                    @if ($canManageTree)
                        <div class="flex items-center gap-2">
                            <button type="button" @click="editing = !editing" class="text-xs text-amber-300 hover:text-amber-200">Editar</button>
                            <form method="POST" action="{{ route('company.clients.installations.destroy', [$client, $installation]) }}" onsubmit="return confirm('¿Eliminar esta instalación?')">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="vista" value="supervision">
                                <button type="submit" class="text-xs text-rose-400 hover:text-rose-300">Eliminar</button>
                            </form>
                        </div>
                    @endif
                </div>

                @if ($canManageTree)
                    <div x-show="editing" x-cloak>
                        @include('modules.company.clients.partials.installation-form', [
                            'client' => $client,
                            'installation' => $installation,
                            'vista' => 'supervision',
                            'accent' => 'amber',
                        ])
                    </div>
                @endif

                <ul class="space-y-2">
                    @forelse ($installation->supervisorPosts as $post)
                        <li class="rounded-md border border-slate-800 px-3 py-2" x-data="{ editingPost: false }">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm text-slate-200">
                                    {{ $post->name }}
                                    <span class="text-[10px] {{ $post->is_active ? 'text-emerald-400' : 'text-rose-400' }}">{{ $post->is_active ? 'activo' : 'inactivo' }}</span>
                                </p>
                                @if ($canManageTree)
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="editingPost = !editingPost" class="text-xs text-amber-300">Editar</button>
                                        <form method="POST" action="{{ route('company.clients.posts.destroy', [$client, $post]) }}" onsubmit="return confirm('¿Eliminar este puesto?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-rose-400">Eliminar</button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                            @if ($canManageTree)
                                <form x-show="editingPost" x-cloak method="POST" action="{{ route('company.clients.posts.update', [$client, $post]) }}" class="mt-2 grid sm:grid-cols-3 gap-2">
                                    @csrf
                                    @method('PUT')
                                    <select name="installation_id" class="rounded-lg bg-slate-950 border border-slate-700 px-2 py-1.5 text-xs text-white">
                                        @foreach ($installations as $option)
                                            <option value="{{ $option->id }}" @selected($option->id === $post->installation_id)>{{ $option->name }}</option>
                                        @endforeach
                                    </select>
                                    <input type="text" name="name" value="{{ $post->name }}" required class="rounded-lg bg-slate-950 border border-slate-700 px-2 py-1.5 text-xs text-white">
                                    <div class="flex items-center gap-2">
                                        <label class="inline-flex items-center gap-1 text-[11px] text-slate-300">
                                            <input type="hidden" name="is_active" value="0">
                                            <input type="checkbox" name="is_active" value="1" @checked($post->is_active) class="rounded border-slate-700 text-amber-500">
                                            Activo
                                        </label>
                                        <button type="submit" class="rounded-lg bg-amber-600 px-2 py-1 text-[11px] font-semibold text-white">OK</button>
                                    </div>
                                </form>
                            @endif
                        </li>
                    @empty
                        <li class="text-xs text-slate-500">Sin puestos. Sin ellos la app no puede guardar revista.</li>
                    @endforelse
                </ul>

                @if ($canManageTree)
                    <form method="POST" action="{{ route('company.clients.posts.store', $client) }}" class="grid sm:grid-cols-3 gap-2 items-end">
                        @csrf
                        <input type="hidden" name="installation_id" value="{{ $installation->id }}">
                        <div class="sm:col-span-2">
                            <label class="block text-[11px] text-slate-500 mb-1">Nuevo puesto</label>
                            <input type="text" name="name" required placeholder="Portería principal" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-2 py-1.5 text-xs text-white">
                        </div>
                        <button type="submit" class="rounded-lg bg-slate-800 px-3 py-1.5 text-xs font-semibold text-slate-200 hover:bg-slate-700">Agregar puesto</button>
                    </form>
                @endif
            </article>
        @empty
            <p class="text-sm text-slate-500">Aún no hay instalaciones. Créela aquí y luego sus puestos de Supervisión.</p>
        @endforelse
    </div>
</section>

<section class="rounded-lg border border-slate-800 bg-slate-900/80 p-4">
    <h3 class="text-sm font-semibold text-white">Revistas recientes</h3>
    <ul class="mt-3 space-y-2">
        @forelse ($proReviews as $review)
            <li class="rounded-lg border border-slate-800 bg-slate-950/40 px-3 py-2 text-sm">
                <p class="text-slate-200">{{ $review->shift?->user?->name ?? 'Supervisor' }}</p>
                <p class="text-xs text-slate-500">
                    {{ $review->recorded_at?->format('d/m/Y H:i') }}
                    @if ($review->supervisorPost)
                        · {{ $review->supervisorPost->installation?->name }} · {{ $review->supervisorPost->name }}
                    @endif
                    @if ($review->has_novelty)
                        · novedad
                    @endif
                    · {{ $review->notes ?: 'Sin notas' }}
                </p>
            </li>
        @empty
            <li class="text-sm text-slate-500">Aún no hay revistas de Supervisión en este sitio.</li>
        @endforelse
    </ul>
</section>
