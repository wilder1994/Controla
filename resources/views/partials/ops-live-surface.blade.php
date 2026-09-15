@php
    $opsCompanyId = $opsCompanyId ?? (auth()->user()?->security_company_id);
    $opsClientId = $opsClientId ?? null;
@endphp
<div
    class="contents"
    x-data="opsLiveSurface"
    data-company="{{ $opsCompanyId ?: '' }}"
    data-client="{{ $opsClientId ?: '' }}"
>
    <div x-show="toast" x-cloak
         class="pointer-events-none fixed bottom-4 right-4 z-[90] max-w-sm rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-xs text-slate-200 shadow-xl">
        <span x-text="toast"></span>
    </div>
</div>
