@php
    $url = $url ?? '';
    $name = $name ?? null;
@endphp
<div class="space-y-1" x-data="{ copied: false }">
    @if (filled($name))
        <p class="text-sm text-slate-300 break-words" title="{{ $name }}">{{ $name }}</p>
    @endif
    <div class="flex min-w-0 gap-2">
        <input type="text" readonly value="{{ $url }}"
               class="min-w-0 w-full h-9 px-3 text-xs font-mono rounded-lg border border-slate-700 bg-slate-950 text-slate-200">
        <button type="button"
                class="h-9 px-3 rounded-lg border border-slate-700 text-xs font-semibold text-slate-200 hover:bg-slate-800 shrink-0"
                @click="navigator.clipboard.writeText(@js($url)).then(() => { copied = true; setTimeout(() => copied = false, 1600) }).catch(() => window.prompt('Copie el enlace', @js($url)))">
            <span x-text="copied ? 'Copiado' : 'Copiar'"></span>
        </button>
    </div>
</div>
