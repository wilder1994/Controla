@php
    use App\Support\Tenancy\CompanySubscriptionState;

    $fmt = fn (float $n) => '$'.number_format($n, 0, ',', '.');
@endphp

<x-admin-layout title="Empresas">
    <div class="flex flex-col flex-1 min-h-0 gap-3">
        {{-- KPIs cartera (sin bloque de título: el layout ya muestra «Empresas») --}}
        <div class="flex flex-col lg:flex-row gap-2.5 shrink-0 lg:justify-stretch">
            {{-- RIESGO COMERCIAL --}}
            <div class="rounded-xl border border-slate-700/80 bg-slate-900/90 px-3 py-2.5 min-w-0 flex-1">
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2 min-w-0">
                        <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                        <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-200">Riesgo comercial</span>
                    </div>
                    <span class="text-[10px] text-slate-500 shrink-0">Datos actualizados</span>
                </div>
                <div class="mt-2 grid grid-cols-2 gap-3 items-center relative">
                    <div class="flex items-center justify-center min-h-[96px]">
                        <p class="text-3xl font-bold text-white tabular-nums leading-none">{{ $kpis['companies_risk_total'] }}</p>
                    </div>
                    <div class="pointer-events-none absolute left-1/2 top-2 bottom-2 w-px -translate-x-1/2 bg-gradient-to-b from-transparent via-slate-600/70 to-transparent" aria-hidden="true"></div>
                    <div class="min-w-0 space-y-1">
                        <div class="flex items-center gap-2 rounded-md bg-amber-600/90 px-2 py-1">
                            <div class="min-w-0 flex-1">
                                <p class="text-[9px] font-semibold uppercase tracking-wide text-amber-100/90 leading-none">Suspendidas</p>
                                <p class="text-sm font-bold text-white tabular-nums leading-tight mt-0.5">{{ $kpis['companies_suspended'] }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 rounded-md bg-slate-800 px-2 py-1">
                            <div class="min-w-0 flex-1">
                                <p class="text-[9px] font-semibold uppercase tracking-wide text-slate-400 leading-none">Archivadas</p>
                                <p class="text-sm font-bold text-slate-100 tabular-nums leading-tight mt-0.5">{{ $kpis['companies_archived'] }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 rounded-md bg-slate-950/80 px-2 py-1 border border-slate-700/60">
                            <div class="min-w-0 flex-1">
                                <p class="text-[9px] font-semibold uppercase tracking-wide text-slate-500 leading-none">Eliminadas</p>
                                <p class="text-sm font-bold text-slate-200 tabular-nums leading-tight mt-0.5">{{ $kpis['companies_deleted'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TOTAL EMPRESAS --}}
            <div class="rounded-xl border border-slate-700/80 bg-slate-900/90 px-3 py-2.5 min-w-0 flex-1">
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2 min-w-0">
                        <svg class="w-4 h-4 text-violet-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M4 21V5a1 1 0 011-1h7v17H4zm9 0V9h6a1 1 0 011 1v11h-7z" />
                            <path stroke-linecap="round" stroke-width="1.6" d="M7 8h2M7 12h2M7 16h2M15 13h2M15 17h2" />
                        </svg>
                        <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-200">Total empresas</span>
                    </div>
                    <span class="text-[10px] text-slate-500 shrink-0">Datos actualizados</span>
                </div>
                <div class="mt-2 grid grid-cols-2 gap-3 items-center relative">
                    <div class="flex items-center justify-center min-h-[96px]">
                        <p class="text-3xl font-bold text-white tabular-nums leading-none">{{ $kpis['companies_total'] }}</p>
                    </div>
                    <div class="pointer-events-none absolute left-1/2 top-2 bottom-2 w-px -translate-x-1/2 bg-gradient-to-b from-transparent via-slate-600/70 to-transparent" aria-hidden="true"></div>
                    <div class="min-w-0 space-y-1.5">
                        <div class="flex items-center gap-2 rounded-md bg-violet-600 px-2.5 py-1.5">
                            <div class="min-w-0 flex-1">
                                <p class="text-[9px] font-semibold uppercase tracking-wide text-violet-100/90 leading-none">Activas</p>
                                <p class="text-sm font-bold text-white tabular-nums leading-tight mt-0.5">{{ $kpis['companies_active'] }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 rounded-md bg-slate-800 px-2.5 py-1.5">
                            <div class="min-w-0 flex-1">
                                <p class="text-[9px] font-semibold uppercase tracking-wide text-slate-400 leading-none">Archivadas</p>
                                <p class="text-sm font-bold text-slate-100 tabular-nums leading-tight mt-0.5">{{ $kpis['companies_archived'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TOTAL CONJUNTOS --}}
            <div class="rounded-xl border border-slate-700/80 bg-slate-900/90 px-3 py-2.5 min-w-0 flex-1">
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2 min-w-0">
                        <svg class="w-4 h-4 text-violet-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M3 21h18M5 21V8l7-4 7 4v13M9 21v-6h6v6"/>
                            <path stroke-linecap="round" stroke-width="1.6" d="M10 10h1M13 10h1M10 13h1M13 13h1"/>
                        </svg>
                        <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-200">Total conjuntos</span>
                    </div>
                    <span class="text-[10px] text-slate-500 shrink-0">Datos actualizados</span>
                </div>
                <div class="mt-2 grid grid-cols-2 gap-3 items-center relative">
                    <div class="flex items-center justify-center min-h-[96px]">
                        <p class="text-3xl font-bold text-white tabular-nums leading-none">{{ $kpis['clients_total'] }}</p>
                    </div>
                    <div class="pointer-events-none absolute left-1/2 top-2 bottom-2 w-px -translate-x-1/2 bg-gradient-to-b from-transparent via-slate-600/70 to-transparent" aria-hidden="true"></div>
                    <div class="min-w-0 space-y-1.5">
                        <div class="flex items-center gap-2 rounded-md bg-violet-600 px-2.5 py-1.5">
                            <div class="min-w-0 flex-1">
                                <p class="text-[9px] font-semibold uppercase tracking-wide text-violet-100/90 leading-none">Operativos</p>
                                <p class="text-sm font-bold text-white tabular-nums leading-tight mt-0.5">{{ $kpis['clients_operational'] }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 rounded-md bg-slate-800 px-2.5 py-1.5">
                            <div class="min-w-0 flex-1">
                                <p class="text-[9px] font-semibold uppercase tracking-wide text-slate-400 leading-none">Archivados</p>
                                <p class="text-sm font-bold text-slate-100 tabular-nums leading-tight mt-0.5">{{ $kpis['clients_archived'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex-1 min-h-0 rounded-lg border border-slate-800 bg-slate-900/80 overflow-hidden flex flex-col">
            <div class="flex-1 overflow-y-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-950/60 text-xs uppercase tracking-wide text-slate-500 sticky top-0">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium">Empresa</th>
                            <th class="px-4 py-2 text-left font-medium">Paquete</th>
                            <th class="px-4 py-2 text-left font-medium">Ciclo</th>
                            <th class="px-4 py-2 text-left font-medium">Cupo</th>
                            <th class="px-4 py-2 text-left font-medium">Contratado</th>
                            <th class="px-4 py-2 text-left font-medium">Estado</th>
                            <th class="px-4 py-2 text-right font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @forelse ($companies as $company)
                            @php $bucket = CompanySubscriptionState::bucket($company); @endphp
                            <tr class="hover:bg-slate-800/30">
                                <td class="px-4 py-2.5">
                                    <p class="font-medium text-slate-200">{{ $company->trade_name }}</p>
                                    <p class="text-xs text-slate-600">{{ $company->tax_id }}</p>
                                </td>
                                <td class="px-4 py-2.5 text-slate-300">{{ $company->packageLabel() }}</td>
                                <td class="px-4 py-2.5">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs {{ $company->billing_cycle?->value === 'annual' ? 'bg-emerald-900/30 text-emerald-300' : 'bg-slate-800 text-slate-400' }}">
                                        {{ $company->billingPeriodLabel() }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-slate-300 tabular-nums">
                                    {{ $company->operational_clients_count ?? $company->clients_count }} / {{ $company->max_clients }}
                                </td>
                                <td class="px-4 py-2.5 text-slate-300 tabular-nums">
                                    {{ $fmt($company->contractedAmount()) }}
                                    <span class="text-slate-600 text-xs">{{ $company->billing_cycle?->value === 'annual' ? '/año' : '/mes' }}</span>
                                </td>
                                <td class="px-4 py-2.5">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs
                                        @if($bucket->value === 'current') bg-emerald-900/30 text-emerald-300
                                        @elseif($bucket->value === 'due_soon') bg-amber-900/30 text-amber-300
                                        @elseif($bucket->value === 'overdue') bg-red-900/30 text-red-300
                                        @elseif($bucket->value === 'suspended') bg-orange-900/40 text-orange-300
                                        @else bg-slate-800 text-slate-400 @endif">
                                        {{ $bucket->label() }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-right">
                                    <a href="{{ route('admin.companies.show', $company) }}" class="text-xs text-violet-400 hover:text-violet-300 font-medium">Gestionar</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-sm text-slate-500">No hay empresas registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($companies->hasPages())
            <div class="shrink-0">{{ $companies->links() }}</div>
        @endif
    </div>
</x-admin-layout>
