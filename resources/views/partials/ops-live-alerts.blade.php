@php
    $opsPoll = $opsPoll ?? null;
    $opsPanicUrl = $opsPanicUrl ?? '';
    $showPanic = (bool) ($showPanic ?? false);
@endphp
@if ($opsPoll)
<div
    x-data="opsLiveAlerts"
    x-init="pollUrl = @js($opsPoll); panicUrl = @js($opsPanicUrl); csrf = @js(csrf_token()); init()"
>
    <template x-teleport="body">
        <div x-show="open" x-cloak class="fixed inset-0 z-[80] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-red-950/80"></div>
            <div class="relative w-full max-w-lg rounded-2xl border border-red-500 bg-slate-950 p-6 text-center shadow-2xl">
                <p class="text-xs uppercase tracking-widest text-red-400">Alerta</p>
                <h3 class="mt-2 text-2xl font-semibold text-white" x-text="title"></h3>
                <p class="mt-3 text-sm text-slate-200" x-text="body"></p>
                <button type="button" class="mt-6 inline-flex h-10 items-center rounded-lg bg-red-600 px-5 text-sm font-medium text-white" @click="ack()">Enterado</button>
            </div>
        </div>
    </template>

    @if ($showPanic)
        <template x-teleport="body">
            <div x-show="panicOpen" class="fixed inset-0 z-[70] flex items-center justify-center p-4" x-cloak>
                <div class="absolute inset-0 bg-black/60" @click="panicOpen = false"></div>
                <div class="relative w-full max-w-md rounded-2xl border border-slate-700 bg-slate-900 p-5">
                    <p class="text-lg font-semibold text-white">¿Activar pánico?</p>
                    <p class="mt-1 text-sm text-slate-400">Se avisa al personal de la empresa. Tú no verás ni oirás la alerta.</p>
                    <textarea x-model="note" rows="2" maxlength="240" placeholder="Nota opcional" class="mt-3 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"></textarea>
                    <div class="mt-4 flex gap-2">
                        <button type="button" class="flex-1 h-9 rounded-lg border border-slate-700 text-sm text-slate-300" @click="panicOpen = false">Cancelar</button>
                        <button type="button" class="flex-1 h-9 rounded-lg bg-red-600 text-sm text-white" @click="sendPanic()">Activar</button>
                    </div>
                </div>
            </div>
        </template>
        <button type="button" class="w-full mb-2 inline-flex items-center justify-center h-9 rounded-lg bg-red-700 text-sm font-medium text-white hover:bg-red-600" @click="panicOpen = true; locate()">
            Pánico
        </button>
    @endif
</div>
@endif
