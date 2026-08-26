@php
    use App\Enums\ClientLifecycle;

    $fmt = fn (?float $n) => $n !== null ? '$'.number_format($n, 0, ',', '.') : '—';
    $riskClass = match ($riskLabel) {
        'Alto' => 'text-rose-300 border-rose-800/50 bg-rose-950/30',
        'Medio' => 'text-amber-300 border-amber-800/50 bg-amber-950/30',
        default => 'text-emerald-300 border-emerald-800/50 bg-emerald-950/30',
    };
@endphp

<x-admin-layout :title="'Empresa: '.$company->displayName()">
    @include('modules.admin.companies.partials.nav-slots', [
        'company' => $company,
        'companyNavActive' => 'show',
    ])

    <div
        class="w-full space-y-4"
        x-data="{
            payOpen: {{ old('action_context') === 'pay' ? 'true' : 'false' }},
            cancelOpen: {{ old('action_context') === 'cancel' ? 'true' : 'false' }},
            changeOpen: {{ old('action_context') === 'schedule' ? 'true' : 'false' }},
            reactivateOpen: false
        }"
    >
        @if ($company->hasPendingCancellation())
            <div class="rounded-lg border border-amber-800/50 bg-amber-950/30 px-4 py-3 text-sm text-amber-100">
                Cancelación programada
                @if ($company->package_ends_at)
                    · acceso hasta {{ $company->package_ends_at->format('d/m/Y') }}
                @endif
                @if ($company->cancellation_reason)
                    <span class="block text-xs text-amber-200/80 mt-1">Motivo: {{ $company->cancellation_reason }}</span>
                @endif
            </div>
        @endif

        @if ($company->hasScheduledPackageChange())
            <div class="rounded-lg border border-violet-800/50 bg-violet-950/30 px-4 py-3 text-sm text-violet-100">
                Cambio de plan programado a
                <strong>{{ $company->scheduled_package_sku }}</strong>
                ({{ $company->scheduled_billing_cycle }})
                @if ($company->scheduled_change_at)
                    · aplica el {{ $company->scheduled_change_at->format('d/m/Y') }}
                @endif
            </div>
        @endif

        <div class="rounded-lg border border-slate-800 bg-slate-900/80 p-4 sm:p-5 space-y-4">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                <div class="rounded-lg border border-slate-800 bg-slate-950/50 px-4 py-3">
                    <p class="text-xs text-slate-500">Cupo operativo</p>
                    <p class="mt-1 text-lg font-semibold text-white tabular-nums">
                        {{ $company->operational_clients_count ?? 0 }} / {{ $company->max_clients }}
                    </p>
                </div>
                <div class="rounded-lg border border-slate-800 bg-slate-950/50 px-4 py-3">
                    <p class="text-xs text-slate-500">Contratado {{ $company->billing_cycle?->value === 'annual' ? '/ año' : '/ mes' }}</p>
                    <p class="mt-1 text-lg font-semibold text-white tabular-nums">{{ $fmt($company->contractedAmount()) }}</p>
                </div>
                <div class="rounded-lg border border-slate-800 bg-slate-950/50 px-4 py-3">
                    <p class="text-xs text-slate-500">A renovación</p>
                    <p class="mt-1 text-lg font-semibold {{ ($daysToRenewal !== null && $daysToRenewal < 45) ? 'text-amber-300' : 'text-white' }} tabular-nums">
                        {{ $daysToRenewal !== null ? $daysToRenewal.' d' : '—' }}
                    </p>
                </div>
                <div class="rounded-lg border border-slate-800 bg-slate-950/50 px-4 py-3">
                    <p class="text-xs text-slate-500">Pagos pendientes</p>
                    <p class="mt-1 text-lg font-semibold {{ $pendingPaymentsCount > 0 ? 'text-amber-300' : 'text-white' }} tabular-nums">
                        {{ $pendingPaymentsCount }}
                    </p>
                </div>
                <div class="rounded-lg border border-slate-800 bg-slate-950/50 px-4 py-3">
                    <p class="text-xs text-slate-500">Alertas ops</p>
                    <p class="mt-1 text-lg font-semibold text-white tabular-nums">{{ $opsAlertsCount }}</p>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <span class="rounded-full bg-slate-800 px-2.5 py-0.5 text-xs text-slate-400">
                    Modalidad · {{ $company->package_modality?->label() ?? '—' }}
                </span>
                <span class="rounded-full {{ $company->billing_cycle?->value === 'annual' ? 'bg-emerald-950/40 text-emerald-300' : 'bg-slate-800 text-slate-400' }} px-2.5 py-0.5 text-xs">
                    Ciclo · {{ $company->billingPeriodLabel() }}
                </span>
                @if ($company->package_ends_at)
                    <span class="rounded-full bg-slate-800 px-2.5 py-0.5 text-xs text-slate-400">
                        Vigencia {{ $company->package_starts_at?->format('d/m/Y') ?? '—' }}
                        → {{ $company->package_ends_at->format('d/m/Y') }}
                    </span>
                @endif
                <span class="rounded-full border px-2.5 py-0.5 text-xs {{ $riskClass }}">
                    Riesgo · {{ $riskLabel }}
                </span>
            </div>

            <div>
                <div class="flex items-center justify-between text-xs text-slate-500 mb-1.5">
                    <span>Uso de cupo</span>
                    <span class="tabular-nums">{{ $quotaPct }}%</span>
                </div>
                <div class="h-1.5 rounded-full bg-slate-800 overflow-hidden">
                    <div class="h-full rounded-full bg-violet-500" style="width: {{ max($quotaPct, $quotaPct > 0 ? 2 : 0) }}%"></div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 lg:items-stretch">
            <section class="rounded-lg border border-slate-800 bg-slate-900/80 p-4 min-w-0 h-full flex flex-col">
                <div class="flex items-center justify-between gap-2 mb-3 shrink-0">
                    <h3 class="text-sm font-semibold text-white">Cartera de conjuntos</h3>
                    <span class="text-xs text-slate-500">
                        {{ $portfolioClients->where('lifecycle', ClientLifecycle::Active)->count() }} activos
                    </span>
                </div>
                <div class="flex-1 min-h-0 overflow-y-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-xs uppercase tracking-wide text-slate-500 sticky top-0 bg-slate-900/95">
                            <tr>
                                <th class="pb-2 text-left font-medium">Conjunto</th>
                                <th class="pb-2 text-left font-medium">Lifecycle</th>
                                <th class="pb-2 text-center font-medium">Geo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800">
                            @forelse ($portfolioClients as $client)
                                <tr>
                                    <td class="py-2 text-slate-200">{{ $client->name }}</td>
                                    <td class="py-2">
                                        <span @class([
                                            'text-xs',
                                            'text-emerald-400' => $client->lifecycle === ClientLifecycle::Active,
                                            'text-slate-500' => $client->lifecycle !== ClientLifecycle::Active,
                                        ])>
                                            {{ $client->lifecycle?->label() ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="py-2 text-center text-xs text-slate-400">
                                        {{ ($client->latitude && $client->longitude) ? 'Sí' : 'No' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-6 text-center text-slate-500">Sin conjuntos en cartera.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <p class="mt-2 text-xs text-slate-600 shrink-0">Usa «Entrar como empresa» para operar conjuntos y portería.</p>
            </section>

            <section class="rounded-lg border border-slate-800 bg-slate-900/80 p-4 space-y-4 min-w-0 h-full flex flex-col">
                <div>
                    <h3 class="text-sm font-semibold text-white">Paquete y ciclo</h3>
                    <p class="text-xs text-slate-500 mt-1">
                        Plan vigente y acciones de membresía. Los cambios de cupo se programan con pago al fin del periodo.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-slate-500">Paquete (cupo × modalidad)</p>
                        <p class="mt-1 text-sm font-medium text-white">{{ $company->packageLabel() }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Ciclo de facturación</p>
                        <p class="mt-1 text-sm font-medium text-white">{{ $company->billing_cycle?->label() ?? '—' }}</p>
                    </div>
                </div>

                <div class="rounded-lg border border-violet-800/40 bg-violet-950/20 p-4 grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                    <div>
                        <p class="text-xs text-slate-500 uppercase">Mensual (ref.)</p>
                        <p class="text-base font-semibold text-white tabular-nums">{{ $fmt($quote->priceMonthly) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-emerald-400/80 uppercase">Anual (recomendado)</p>
                        <p class="text-base font-semibold text-emerald-300 tabular-nums">{{ $fmt($quoteAnnual->priceAnnual) }}</p>
                        <p class="text-xs text-emerald-400/70">Ahorras {{ $fmt($quoteAnnual->annualSavings) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 uppercase">Desc. volumen</p>
                        <p class="text-base font-semibold text-white">−{{ $quote->volumeDiscountLabel() }}</p>
                        <p class="text-xs text-slate-500 tabular-nums">{{ $fmt($quote->effectiveUnitMonthly) }}/cliente eff.</p>
                    </div>
                </div>

                @can('platform.companies.manage')
                    <form method="POST" action="{{ route('admin.companies.supervision-package.update', $company) }}" class="rounded-lg border border-amber-800/40 bg-amber-950/10 p-4 space-y-3">
                        @csrf
                        @method('PUT')
                        <div>
                            <p class="text-xs text-slate-500">Supervisión (sitios GPS)</p>
                            <p class="mt-1 text-sm font-medium text-white">{{ $company->supervision_package_sku?->label() ?? 'Sin Supervisión' }}</p>
                        </div>
                        <select name="supervision_package_sku" class="w-full h-9 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white">
                            <option value="">Sin Supervisión</option>
                            @foreach (\App\Enums\SupervisionPackageSku::options() as $value => $label)
                                <option value="{{ $value }}" @selected(old('supervision_package_sku', $company->supervision_package_sku?->value) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-ui.button type="submit" variant="secondary" size="sm">Asignar Supervisión</x-ui.button>
                    </form>
                @endcan

                @can('platform.companies.manage')
                    <div class="flex flex-wrap items-center gap-2 pt-1 border-t border-slate-800">
                        <x-ui.button type="button" variant="secondary" size="md" @click="payOpen = true">
                            Pagar factura
                        </x-ui.button>
                        @if ($isUpToDate && ! $company->hasPendingCancellation())
                            <x-ui.button type="button" variant="secondary" size="md" @click="cancelOpen = true">
                                Cancelar membresía
                            </x-ui.button>
                        @endif
                        @if ($isUpToDate)
                            <x-ui.button type="button" variant="secondary" size="md" @click="changeOpen = true">
                                Programar cambio
                            </x-ui.button>
                        @endif

                        @if ($company->canUndoCancellation())
                            <x-ui.button type="button" variant="secondary" size="md" @click="reactivateOpen = true">
                                Reactivar membresía
                            </x-ui.button>
                        @elseif ($company->needsPaidReactivation())
                            <x-ui.button type="button" variant="secondary" size="md" @click="payOpen = true">
                                Reactivar membresía
                            </x-ui.button>
                        @else
                            <x-ui.button
                                type="button"
                                variant="secondary"
                                size="md"
                                disabled
                                class="opacity-40 cursor-not-allowed hover:bg-transparent"
                                title="Disponible cuando la membresía esté cancelada"
                            >
                                Reactivar membresía
                            </x-ui.button>
                        @endif
                    </div>
                @else
                    <p class="text-xs text-slate-500">No tienes permiso para gestionar la membresía.</p>
                @endcan

                @if ($company->package_modality)
                    <ul class="flex flex-wrap gap-2">
                        @foreach ($company->package_modality->features() as $feature)
                            <li class="rounded-full bg-slate-800 px-2.5 py-0.5 text-xs text-slate-400">{{ str_replace('_', ' ', $feature) }}</li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <section class="rounded-lg border border-slate-800 bg-slate-900/80 p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-white">Historial de pagos</h3>
                        <p class="text-xs text-slate-500 mt-1">Facturas pagadas registradas</p>
                    </div>
                    <a href="{{ route('admin.companies.historial', $company) }}" class="text-xs text-violet-400 hover:text-violet-300 shrink-0">
                        Ver historial →
                    </a>
                </div>
                <p class="mt-4 text-3xl font-semibold text-white tabular-nums">{{ $paidInvoicesCount }}</p>
                <p class="text-xs text-slate-500 mt-1">
                    {{ $paidInvoicesCount === 1 ? 'pago completado' : 'pagos completados' }}
                </p>
            </section>

            <section class="rounded-lg border border-slate-800 bg-slate-900/80 p-4">
                <h3 class="text-sm font-semibold text-white mb-3">Contacto y soporte</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                    <div>
                        <p class="text-xs text-slate-500">Admin empresa</p>
                        <p class="mt-1 font-medium text-slate-200 break-all">{{ $companyAdminEmail }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Último pago</p>
                        <p class="mt-1 font-medium text-slate-200">
                            @if ($latestPayment)
                                {{ ($latestPayment->paid_at ?? $latestPayment->created_at)?->format('d/m/Y') }}
                                · {{ $latestPayment->method?->label() ?? '—' }}
                            @else
                                —
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Docs legales</p>
                        <a href="{{ route('admin.documents.expedientes.show', $company) }}" class="mt-1 inline-block font-medium text-violet-400 hover:text-violet-300">
                            Abrir expediente →
                        </a>
                    </div>
                </div>
            </section>
        </div>

        @include('modules.admin.companies.partials.pay-invoice-modal', [
            'company' => $company,
            'hasAcceptance' => $hasAcceptance,
            'isUpToDate' => $isUpToDate,
        ])
        @include('modules.admin.companies.partials.cancel-membership-modal', ['company' => $company])
        @include('modules.admin.companies.partials.reactivate-membership-modal', ['company' => $company])
        @include('modules.admin.companies.partials.schedule-package-modal', [
            'company' => $company,
            'packageOptions' => $packageOptions,
            'cycleOptions' => $cycleOptions,
        ])
    </div>
</x-admin-layout>
