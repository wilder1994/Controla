@php
    $links = [];
    if (isset($publicUrl)) {
        $links[] = ['name' => null, 'url' => $publicUrl];
    } else {
        foreach ($shareClients ?? [] as $shareClient) {
            $links[] = [
                'name' => $shareClient->name,
                'url' => route('observatory.public.show', $shareClient->slug),
            ];
        }
    }
@endphp
<div x-data="{ open: false }" class="shrink-0">
    <button type="button"
            class="h-9 px-4 rounded-lg border border-slate-700 text-sm text-slate-200 hover:bg-slate-800"
            @click="open = true">
        Compartir link
    </button>
    <template x-teleport="body">
        <div x-show="open" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             @keydown.escape.window="open = false">
            <div class="absolute inset-0 bg-slate-950/70" @click="open = false"></div>
            <div class="relative w-full max-w-lg rounded-xl border border-slate-700 bg-slate-900 p-5 space-y-4 shadow-2xl"
                 @click.stop>
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-slate-500">Comunidad</p>
                    <h3 class="text-lg font-semibold text-white">Link para reportar</h3>
                    <p class="mt-1 text-sm text-slate-400">Cualquiera con el enlace puede denunciar desde el celular, sin usuario.</p>
                </div>
                @forelse ($links as $link)
                    @include('modules.observatory.partials.public-link', [
                        'url' => $link['url'],
                        'name' => $link['name'],
                    ])
                @empty
                    <p class="text-sm text-slate-500">No hay clientes activos con slug.</p>
                @endforelse
                <div class="flex justify-end">
                    <button type="button"
                            class="h-9 px-4 rounded-lg border border-slate-700 text-sm text-slate-200 hover:bg-slate-800"
                            @click="open = false">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
