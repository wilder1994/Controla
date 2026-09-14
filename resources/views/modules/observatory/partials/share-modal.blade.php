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
    $linkCount = count($links);
@endphp
<div x-data="{ open: false, q: '' }" class="shrink-0">
    <button type="button"
            class="h-9 px-4 rounded-lg border border-slate-700 text-sm text-slate-200 hover:bg-slate-800"
            @click="open = true">
        Compartir link
    </button>
    <template x-teleport="body">
        <div x-show="open" x-cloak
             class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto p-4 sm:items-center"
             @keydown.escape.window="open = false">
            <div class="absolute inset-0 bg-slate-950/70" @click="open = false"></div>
            <div class="relative my-auto flex max-h-[min(90vh,42rem)] w-full max-w-lg flex-col rounded-xl border border-slate-700 bg-slate-900 shadow-2xl"
                 @click.stop>
                <div class="shrink-0 space-y-1 border-b border-slate-800 px-5 py-4">
                    <p class="text-[11px] uppercase tracking-wide text-slate-500">Comunidad</p>
                    <h3 class="text-lg font-semibold text-white">Link para reportar</h3>
                    <p class="text-sm text-slate-400">Cualquiera con el enlace puede denunciar desde el celular, sin usuario.</p>
                    @if ($linkCount > 6)
                        <input type="search" x-model="q" placeholder="Buscar cliente"
                               class="mt-2 w-full h-9 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white">
                    @endif
                </div>
                <div class="min-h-0 flex-1 space-y-3 overflow-y-auto px-5 py-3">
                    @forelse ($links as $link)
                        <div @if (filled($link['name'])) x-show="!q.trim() || @js(mb_strtolower((string) $link['name'])).includes(q.trim().toLowerCase())" @endif>
                            @include('modules.observatory.partials.public-link', [
                                'url' => $link['url'],
                                'name' => $link['name'],
                            ])
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No hay clientes activos con slug.</p>
                    @endforelse
                </div>
                <div class="flex shrink-0 justify-end border-t border-slate-800 px-5 py-3">
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
