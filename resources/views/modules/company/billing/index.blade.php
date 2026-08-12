@php
    $fmt = fn (?float $n) => $n !== null ? '$'.number_format($n, 0, ',', '.') : '—';
@endphp

<x-company-layout title="Facturación y pagos">
    <div class="space-y-4 max-w-2xl">
        <section class="rounded-lg border border-slate-800 bg-slate-900/80 p-4">
            <p class="text-xs text-slate-500">Licencia Controla</p>
            <h3 class="text-sm font-semibold text-white mt-0.5">{{ $company->displayName() }}</h3>
            <p class="text-xs text-slate-500 mt-1">
                Monto contratado: {{ $fmt($company->contractedAmount()) }}
                @if ($company->billing_cycle)
                    · {{ $company->billing_cycle->label() }}
                @endif
            </p>
            <p class="text-[10px] text-slate-600 mt-2">
                Simulador local · driver {{ $gatewayDriver }} · facturación {{ config('billing.mode') }}
            </p>
        </section>

        <section class="rounded-lg border border-slate-800 bg-slate-900/80 p-4">
            <h3 class="text-sm font-semibold text-white">Aceptación contractual</h3>
            @if ($hasAcceptance)
                <p class="mt-2 text-sm text-emerald-300">Aceptación registrada. Puede proceder al pago.</p>
            @else
                <p class="mt-2 text-sm text-amber-300">
                    Pendiente. El súper admin debe registrar la aceptación clickwrap en el expediente comercial antes del pago.
                </p>
            @endif
        </section>

        <section class="rounded-lg border border-slate-800 bg-slate-900/80 p-4">
            <h3 class="text-sm font-semibold text-white">Pago online (simulado)</h3>
            <p class="text-xs text-slate-500 mt-1">
                Checkout interno para pruebas locales. No conecta con proveedores externos.
            </p>

            @if ($pendingPayment)
                <div class="mt-3 rounded-lg border border-indigo-700/50 bg-indigo-900/20 px-3 py-2">
                    <p class="text-sm text-indigo-200">Tiene un pago pendiente de confirmación.</p>
                    <x-ui.button variant="primary" :href="route('billing.checkout.show', $pendingPayment)" size="sm" class="mt-2">
                        Continuar checkout
                    </x-ui.button>
                </div>
            @elseif ($hasAcceptance)
                <form method="POST" action="{{ route('company.billing.checkout') }}" class="mt-4">
                    @csrf
                    <x-ui.button type="submit" variant="primary" size="md">Iniciar pago online simulado</x-ui.button>
                </form>
            @else
                <p class="mt-3 text-sm text-slate-500">Complete la aceptación contractual para habilitar el pago.</p>
            @endif
        </section>

        @if ($latestPayment)
            <section class="rounded-lg border border-slate-800 bg-slate-900/60 p-4 text-sm">
                <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-400">Último movimiento</h3>
                <p class="mt-2 text-slate-300">
                    {{ $latestPayment->status->label() }}
                    · {{ $fmt((float) $latestPayment->amount) }}
                    · {{ $latestPayment->method->label() }}
                </p>
                <p class="text-xs text-slate-500 mt-1">
                    @if ($latestPayment->paid_at)
                        {{ $latestPayment->paid_at->format('d/m/Y H:i') }}
                    @else
                        Creado {{ $latestPayment->created_at->format('d/m/Y H:i') }}
                    @endif
                    @if ($latestPayment->reference)
                        · {{ $latestPayment->reference }}
                    @endif
                </p>
            </section>
        @endif
    </div>
</x-company-layout>
