<div class="relative">
    <div class="flex items-stretch gap-2">
        <div class="relative flex-1">
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
            </div>
            <input
                type="text"
                x-ref="scanInput"
                x-model="scanBuffer"
                @keydown.enter.prevent="handleScan()"
                class="qr-scan-input w-full rounded-lg bg-slate-950 border-slate-700 pl-10 pr-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="Escanear cédula aquí..."
                autocomplete="off"
            >
        </div>
        <button
            type="button"
            @click="$refs.scanInput.focus()"
            class="px-3 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold transition-colors flex items-center gap-1.5"
            title="Enfocar para escanear"
        >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
            Escanear
        </button>
    </div>
    <p class="text-xs text-slate-500 mt-1">Enfoque el lector al código QR y escanee</p>
</div>

@once
<script>
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Tab' || e.keyCode === 9) {
            var el = document.activeElement;
            if (el && el.classList.contains('qr-scan-input')) {
                e.preventDefault();
                var start = el.selectionStart || el.value.length;
                var end = el.selectionEnd || el.value.length;
                el.value = el.value.substring(0, start) + '|' + el.value.substring(end);
                el.selectionStart = el.selectionEnd = start + 1;
                el.dispatchEvent(new Event('input', { bubbles: true }));
            }
        }
    });
</script>
@endonce
