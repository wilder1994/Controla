<x-admin-layout title="Documentos">
    <x-slot:actions>
        <x-ui.button variant="secondary" :href="route('admin.documents.normativa')" size="sm">Normativa</x-ui.button>
        <x-ui.button variant="secondary" :href="route('admin.documents.trd')" size="sm">TRD</x-ui.button>
        <x-ui.button variant="platform" :href="route('admin.documents.expedientes')" size="sm">Expedientes</x-ui.button>
    </x-slot:actions>

    <div class="flex flex-col flex-1 min-h-0 gap-3">
        <div class="flex flex-col lg:flex-row gap-2.5 shrink-0 lg:justify-stretch">
            <div class="rounded-xl border border-slate-700/80 bg-slate-900/90 px-3 py-2.5 min-w-0 flex-1">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-violet-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-200">Expedientes</span>
                </div>
                <p class="mt-2 text-3xl font-bold text-white tabular-nums">{{ $kpis['expedientes_total'] }}</p>
            </div>

            <div class="rounded-xl border border-slate-700/80 bg-slate-900/90 px-3 py-2.5 min-w-0 flex-1">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-200">Sin aceptación</span>
                </div>
                <p class="mt-2 text-3xl font-bold text-white tabular-nums">{{ $kpis['acceptances_pending'] }}</p>
            </div>

            <div class="rounded-xl border border-slate-700/80 bg-slate-900/90 px-3 py-2.5 min-w-0 flex-1">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M9 14l2 2 4-4M7 3h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z"/>
                    </svg>
                    <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-200">Facturas demo (mes)</span>
                </div>
                <p class="mt-2 text-3xl font-bold text-white tabular-nums">{{ $kpis['demo_invoices_month'] }}</p>
            </div>

            <div class="rounded-xl border border-slate-700/80 bg-slate-900/90 px-3 py-2.5 min-w-0 flex-1">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-sky-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-200">Evidencias (30 d)</span>
                </div>
                <p class="mt-2 text-3xl font-bold text-white tabular-nums">{{ $kpis['evidence_events_30d'] }}</p>
            </div>
        </div>

        <div class="rounded-lg border border-slate-800 bg-slate-900/80 p-4 shrink-0">
            <h3 class="text-sm font-semibold text-white">Accesos rápidos</h3>
            <div class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-3">
                <a href="{{ route('admin.documents.normativa') }}" class="rounded-lg border border-slate-700/80 bg-slate-950/50 p-3 hover:border-violet-500/50 transition-colors">
                    <p class="text-sm font-medium text-slate-200">Normoteca</p>
                    <p class="text-xs text-slate-500 mt-1">Contrato, T&C, políticas y procedimiento de ciclo de vida.</p>
                </a>
                <a href="{{ route('admin.documents.trd') }}" class="rounded-lg border border-slate-700/80 bg-slate-950/50 p-3 hover:border-violet-500/50 transition-colors">
                    <p class="text-sm font-medium text-slate-200">TRD</p>
                    <p class="text-xs text-slate-500 mt-1">Series, subseries, retención y disposición final.</p>
                </a>
                <a href="{{ route('admin.documents.expedientes') }}" class="rounded-lg border border-slate-700/80 bg-slate-950/50 p-3 hover:border-violet-500/50 transition-colors">
                    <p class="text-sm font-medium text-slate-200">Expedientes comerciales</p>
                    <p class="text-xs text-slate-500 mt-1">Aceptación clickwrap, pagos y facturas por suscriptor.</p>
                </a>
            </div>
        </div>
    </div>
</x-admin-layout>
