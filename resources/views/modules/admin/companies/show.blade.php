@php
    $fmt = fn (float $n) => '$'.number_format($n, 0, ',', '.');
    $selectClass = 'w-full h-9 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white focus:border-violet-500 focus:ring-1 focus:ring-violet-500/30';
@endphp

<x-admin-layout :title="'Empresa: '.$company->trade_name">
    <div class="max-w-3xl space-y-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <x-ui.button variant="secondary" :href="route('admin.companies.index')" size="sm">← Empresas</x-ui.button>
                <h3 class="mt-3 text-sm font-semibold text-white">{{ $company->trade_name }}</h3>
                <p class="text-xs text-slate-500 mt-0.5">{{ $company->legal_name }} · NIT {{ $company->tax_id }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <x-ui.button variant="secondary" :href="route('admin.dashboard', ['company' => $company->id])" size="sm">
                    Ver en resumen
                </x-ui.button>
                @can('updateProfile', $company)
                    <x-ui.button variant="platform" :href="route('admin.companies.profile.edit', $company)" size="sm">
                        Perfil y ubicación
                    </x-ui.button>
                @endcan
            </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="rounded-lg border border-slate-800 bg-slate-900/60 px-4 py-3">
                <p class="text-xs text-slate-500">Cupo operativo</p>
                <p class="mt-1 text-lg font-semibold text-white tabular-nums">
                    {{ $company->operational_clients_count ?? 0 }} / {{ $company->max_clients }}
                </p>
            </div>
            <div class="rounded-lg border border-slate-800 bg-slate-900/60 px-4 py-3">
                <p class="text-xs text-slate-500">Modalidad</p>
                <p class="mt-1 text-sm font-semibold text-white">{{ $company->package_modality?->label() ?? '—' }}</p>
            </div>
            <div class="rounded-lg border border-slate-800 bg-slate-900/60 px-4 py-3">
                <p class="text-xs text-slate-500">Ciclo</p>
                <p class="mt-1 text-sm font-semibold {{ $company->billing_cycle?->value === 'annual' ? 'text-emerald-400' : 'text-white' }}">
                    {{ $company->billingPeriodLabel() }}
                </p>
            </div>
            <div class="rounded-lg border border-slate-800 bg-slate-900/60 px-4 py-3">
                <p class="text-xs text-slate-500">Contratado</p>
                <p class="mt-1 text-lg font-semibold text-white tabular-nums">{{ $fmt($company->contractedAmount()) }}</p>
                <p class="text-xs text-slate-600">{{ $company->billing_cycle?->value === 'annual' ? 'por año' : 'por mes' }}</p>
            </div>
        </div>

        @if ($company->package_ends_at)
            <div class="rounded-lg border border-slate-800 bg-slate-900/60 px-4 py-3 text-xs text-slate-400">
                Vigencia {{ $company->package_starts_at?->format('d/m/Y') ?? '—' }}
                → {{ $company->package_ends_at->format('d/m/Y') }}
                · {{ $company->subscription_status?->label() ?? 'Activa' }}
            </div>
        @endif

        <section class="rounded-lg border border-slate-800 bg-slate-900/80 p-4 space-y-4">
            <div>
                <h3 class="text-sm font-semibold text-white">Asignar / cambiar paquete</h3>
                <p class="text-xs text-slate-500 mt-1">
                    Precio calculado con la tabla vigente. Al guardar se congela el snapshot en el contrato.
                </p>
            </div>

            @can('platform.companies.manage')
                <form method="POST" action="{{ route('admin.companies.package.update', $company) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-ui.label for="package_sku">Paquete (cupo × modalidad)</x-ui.label>
                            <select id="package_sku" name="package_sku" required class="{{ $selectClass }}">
                                @foreach ($packageOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('package_sku', $company->package_sku?->value) === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            <x-ui.field-error :messages="$errors->get('package_sku')" />
                        </div>
                        <div>
                            <x-ui.label for="billing_cycle">Ciclo de facturación</x-ui.label>
                            <select id="billing_cycle" name="billing_cycle" required class="{{ $selectClass }}">
                                @foreach ($cycleOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('billing_cycle', $company->billing_cycle?->value ?? 'monthly') === $value)>
                                        {{ $label }}{{ $value === 'annual' ? ' · recomendado' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <x-ui.field-error :messages="$errors->get('billing_cycle')" />
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

                    <p class="text-xs text-slate-600">
                        Vista previa con el SKU/ciclo al cargar. Guarda para aplicar el precio exacto al contrato.
                    </p>

                    <x-ui.button type="submit" variant="platform" size="md">Guardar paquete y ciclo</x-ui.button>
                </form>
            @else
                <p class="text-xs text-slate-500">No tienes permiso para cambiar el paquete.</p>
            @endcan
        </section>

        @if ($company->package_modality)
            <section class="rounded-lg border border-slate-800 bg-slate-900/60 p-4">
                <h3 class="text-sm font-semibold text-white mb-2">Capacidades incluidas</h3>
                <ul class="flex flex-wrap gap-2">
                    @foreach ($company->package_modality->features() as $feature)
                        <li class="rounded-full bg-slate-800 px-2.5 py-0.5 text-xs text-slate-400">{{ str_replace('_', ' ', $feature) }}</li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</x-admin-layout>
