@php
    $url = $url ?? '';
    $name = $name ?? null;
@endphp
<div class="flex flex-col sm:flex-row sm:items-center gap-2" x-data="{ copied: false }">
    @if (filled($name))
        <p class="text-sm text-slate-300 sm:w-48 shrink-0 truncate" title="{{ $name }}">{{ $name }}</p>
    @endif
    <div class="flex-1 min-w-0 flex gap-2">
        <input type="text" readonly value="{{ $url }}"
               class="w-full h-9 px-3 text-xs font-mono rounded-lg border border-slate-700 bg-slate-950 text-slate-200">
        <button type="button"
                class="h-9 px-3 rounded-lg border border-slate-700 text-xs font-semibold text-slate-200 hover:bg-slate-800 shrink-0"
                @click="navigator.clipboard.writeText(@js($url)).then(() => { copied = true; setTimeout(() => copied = false, 1600) }).catch(() => window.prompt('Copie el enlace', @js($url)))">
            <span x-text="copied ? 'Copiado' : 'Copiar'"></span>
        </button>
    </div>
</div>
