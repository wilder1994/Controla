@php
    $canManageTree = $canManageTree ?? false;
    $installations = $installations ?? collect();
    $postModalities = $postModalities ?? [];
    $siteAdmins = $siteAdmins ?? [];
@endphp

<section class="rounded-lg border border-slate-800 bg-slate-900/80 p-4 space-y-4">
    <div>
        <h3 class="text-sm font-semibold text-white">Instalaciones y puestos</h3>
        <p class="mt-1 text-xs text-slate-500">
            Sitio, modalidad y vigilantes. Una instalación tiene varios puestos; un puesto, varios vigilantes. No es una puerta.
        </p>
    </div>

    @if ($canManageTree)
        @include('modules.company.clients.partials.installation-form', ['client' => $client, 'vista' => 'sitio', 'accent' => 'indigo', 'siteAdmins' => $siteAdmins])
    @endif

    <div class="space-y-3">
        @forelse ($installations as $installation)
            <article class="rounded-lg border border-slate-800 bg-slate-950/40 p-3 space-y-3" x-data="{ editing: false }">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-sm font-medium text-white">{{ $installation->name }}</p>
                        <a href="{{ route('company.installations.show', $installation) }}" class="text-xs text-indigo-400 hover:text-indigo-300">Ver ficha</a>
                        @if ($installation->is_client_site)
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-indigo-900/40 text-indigo-300">Mismo cliente</span>
                        @endif
                        <span class="text-[10px] px-2 py-0.5 rounded-full {{ $installation->is_active ? 'bg-emerald-900/40 text-emerald-300' : 'bg-rose-900/40 text-rose-300' }}">
                            {{ $installation->is_active ? 'Activa' : 'Inactiva' }}
                        </span>
                        <span class="text-xs text-slate-500">{{ $installation->supervisorPosts->count() }} puesto{{ $installation->supervisorPosts->count() === 1 ? '' : 's' }}</span>
                        @if ($installation->address || $installation->city)
                            <span class="text-xs text-slate-500">{{ $installation->address }}{{ $installation->city ? ' · '.$installation->city : '' }}</span>
                        @endif
                    </div>
                    @if ($canManageTree)
                        <div class="flex items-center gap-2">
                            <button type="button" @click="editing = !editing" class="text-xs text-indigo-300 hover:text-indigo-200">Editar</button>
                            <form method="POST" action="{{ route('company.clients.installations.destroy', [$client, $installation]) }}" onsubmit="return confirm('¿Eliminar esta instalación?')">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="vista" value="sitio">
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
                            'vista' => 'sitio',
                            'accent' => 'indigo',
                            'siteAdmins' => $siteAdmins,
                        ])
                    </div>
                @endif

                @include('modules.company.clients.partials.posts-block', [
                    'client' => $client,
                    'installation' => $installation,
                    'installations' => $installations,
                    'vista' => 'sitio',
                    'accent' => 'indigo',
                    'canManageTree' => $canManageTree,
                    'postModalities' => $postModalities,
                ])
            </article>
        @empty
            <p class="text-sm text-slate-500">Aún no hay instalaciones. Créela aquí y luego sus puestos.</p>
        @endforelse
    </div>
</section>
