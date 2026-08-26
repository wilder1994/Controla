@php
    $fmt = fn (float $n) => '$'.number_format($n, 0, ',', '.');
@endphp

<x-admin-layout title="Tabla de precios">
    <x-slot:actions>
        <x-ui.button
            :variant="$cycle->value === 'monthly' ? 'platform' : 'secondary'"
            :href="route('admin.pricing.edit', ['cycle' => 'monthly'])"
            size="sm">
            Mensual
        </x-ui.button>
        <x-ui.button
            :variant="$cycle->value === 'annual' ? 'platform' : 'secondary'"
            :href="route('admin.pricing.edit', ['cycle' => 'annual'])"
            size="sm">
            Anual recomendado
        </x-ui.button>
    </x-slot:actions>

    <div class="flex flex-col flex-1 min-h-0 gap-4">
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-4 flex-1 min-h-0">
            <section class="xl:col-span-4 rounded-lg border border-slate-800 bg-slate-900/80 p-4 flex flex-col">
                <h3 class="text-sm font-semibold text-white">Precios base</h3>
                <p class="text-xs text-slate-500 mt-0.5">Única entrada manual del súper admin.</p>

                @can('platform.companies.manage')
                    <form method="POST" action="{{ route('admin.pricing.update') }}" class="mt-4 space-y-4 flex-1">
                        @csrf
                        @method('PUT')

                        <div>
                            <x-ui.label for="unit_price_manual">Unitario · Sin hardware</x-ui.label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm">$</span>
                                <x-ui.input accent="platform" type="number" step="1000" min="1000" name="unit_price_manual" id="unit_price_manual"
                                       :value="old('unit_price_manual', (int) $settings->unit_price_manual)"
                                       class="pl-7" />
                            </div>
                            <p class="mt-1 text-xs text-slate-600">COP por cliente / mes · modalidad manual</p>
                            <x-ui.field-error :messages="$errors->get('unit_price_manual')" />
                        </div>

                        <div>
                            <x-ui.label for="unit_price_hardware">Unitario · Con hardware</x-ui.label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm">$</span>
                                <x-ui.input accent="platform" type="number" step="1000" min="1000" name="unit_price_hardware" id="unit_price_hardware"
                                       :value="old('unit_price_hardware', (int) $settings->unit_price_hardware)"
                                       class="pl-7" />
                            </div>
                            <p class="mt-1 text-xs text-slate-600">COP por cliente / mes · lectores, LPR, facial…</p>
                            <x-ui.field-error :messages="$errors->get('unit_price_hardware')" />
                        </div>

                        <div>
                            <x-ui.label for="unit_price_supervision">Unitario · Supervisión Pro</x-ui.label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm">$</span>
                                <x-ui.input accent="platform" type="number" step="1000" min="0" name="unit_price_supervision" id="unit_price_supervision"
                                       :value="old('unit_price_supervision', (int) $settings->unit_price_supervision)"
                                       class="pl-7" />
                            </div>
                            <p class="mt-1 text-xs text-slate-600">COP por sitio / mes · sin hardware</p>
                            <x-ui.field-error :messages="$errors->get('unit_price_supervision')" />
                        </div>

                        <x-ui.button type="submit" variant="platform" size="md" class="w-full">Guardar y recalcular matriz</x-ui.button>
                    </form>
                @else
                    <div class="mt-4 space-y-2 text-sm">
                        <p class="text-slate-400">Manual: <span class="font-semibold text-white tabular-nums">{{ $fmt((float) $settings->unit_price_manual) }}</span></p>
                        <p class="text-slate-400">Hardware: <span class="font-semibold text-white tabular-nums">{{ $fmt((float) $settings->unit_price_hardware) }}</span></p>
                        <p class="text-slate-400">Supervisión Pro: <span class="font-semibold text-white tabular-nums">{{ $fmt((float) $settings->unit_price_supervision) }}</span></p>
                    </div>
                @endcan

                <div class="mt-4 rounded-lg border border-slate-800 bg-slate-950/60 p-3 text-xs text-slate-500 space-y-1">
                    <p>Volumen: 1 (0%) · 5 (10%) · 10 (15%) · 50 (25%) · 100 (30%)</p>
                    <p>Anual: −{{ number_format($annualDiscount * 100, 0) }}% sobre 12 meses del paquete</p>
                    <p>Moneda: {{ $settings->currency }}</p>
                </div>
            </section>

            <section class="xl:col-span-8 rounded-lg border border-slate-800 bg-slate-900/80 flex flex-col min-h-0">
                <div class="px-4 py-3 border-b border-slate-800 flex items-center justify-between gap-2 shrink-0">
                    <div>
                        <h3 class="text-sm font-semibold text-white">Matriz calculada · {{ $cycle->label() }}</h3>
                        <p class="text-xs text-slate-600">Precios de paquete (no editables celda a celda)</p>
                    </div>
                    @if ($cycle === \App\Enums\BillingCycle::Annual)
                        <span class="rounded-full bg-emerald-900/30 border border-emerald-800/50 px-2.5 py-0.5 text-xs text-emerald-300">
                            Ahorro vs 12 mensualidades
                        </span>
                    @endif
                </div>

                <div class="flex-1 overflow-y-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-950/60 text-xs uppercase tracking-wide text-slate-500 sticky top-0">
                            <tr>
                                <th class="px-4 py-2 text-left font-medium">Cupo</th>
                                <th class="px-4 py-2 text-left font-medium">Desc. vol.</th>
                                <th class="px-4 py-2 text-right font-medium">Sin hardware</th>
                                <th class="px-4 py-2 text-right font-medium">Con hardware</th>
                                <th class="px-4 py-2 text-right font-medium">$/cliente eff.</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800">
                            @foreach ($matrix as $row)
                                @php
                                    $cell = $cycle->value === 'annual' ? 'price_annual' : 'price_monthly';
                                    $period = $cycle->value === 'annual' ? '/año' : '/mes';
                                @endphp
                                <tr class="hover:bg-slate-800/30">
                                    <td class="px-4 py-2.5">
                                        <span class="font-medium text-slate-200">{{ $row['size'] }}</span>
                                        <span class="text-slate-600">{{ $row['size'] === 1 ? 'cliente' : 'clientes' }}</span>
                                    </td>
                                    <td class="px-4 py-2.5 text-slate-400">−{{ number_format($row['volume_discount_pct'] * 100, 0) }}%</td>
                                    <td class="px-4 py-2.5 text-right">
                                        <p class="font-medium text-slate-200 tabular-nums">{{ $fmt((float) $row['manual'][$cell]) }}{{ $period }}</p>
                                        @if ($cycle->value === 'annual')
                                            <p class="text-xs text-emerald-400/90 tabular-nums">Ahorras {{ $fmt((float) $row['manual']['annual_savings']) }}</p>
                                        @else
                                            <p class="text-xs text-slate-600 tabular-nums">Lista {{ $fmt((float) $row['manual']['list_monthly_without_volume']) }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 text-right">
                                        <p class="font-medium text-violet-300 tabular-nums">{{ $fmt((float) $row['hardware'][$cell]) }}{{ $period }}</p>
                                        @if ($cycle->value === 'annual')
                                            <p class="text-xs text-emerald-400/90 tabular-nums">Ahorras {{ $fmt((float) $row['hardware']['annual_savings']) }}</p>
                                        @else
                                            <p class="text-xs text-slate-600 tabular-nums">Lista {{ $fmt((float) $row['hardware']['list_monthly_without_volume']) }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 text-right text-slate-500 tabular-nums">
                                        <p>{{ $fmt((float) $row['manual']['effective_unit_monthly']) }}</p>
                                        <p class="text-violet-400/80">{{ $fmt((float) $row['hardware']['effective_unit_monthly']) }}</p>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <section class="rounded-lg border border-slate-800 bg-slate-900/80 flex flex-col min-h-0">
            <div class="px-4 py-3 border-b border-slate-800 flex items-center justify-between gap-2 shrink-0">
                <div>
                    <h3 class="text-sm font-semibold text-white">Supervisión Pro · {{ $cycle->label() }}</h3>
                    <p class="text-xs text-slate-600">Misma matriz de volumen. Empresa con Accesos: Pro = 0 hasta asignar sitios.</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-950/60 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium">Sitios</th>
                            <th class="px-4 py-2 text-left font-medium">Desc. vol.</th>
                            <th class="px-4 py-2 text-right font-medium">Paquete</th>
                            <th class="px-4 py-2 text-right font-medium">$/sitio eff.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @foreach ($supervisionMatrix as $row)
                            @php
                                $cell = $cycle->value === 'annual' ? 'price_annual' : 'price_monthly';
                                $period = $cycle->value === 'annual' ? '/año' : '/mes';
                            @endphp
                            <tr class="hover:bg-slate-800/30">
                                <td class="px-4 py-2.5 font-medium text-slate-200">{{ $row['size'] }}</td>
                                <td class="px-4 py-2.5 text-slate-400">−{{ number_format($row['volume_discount_pct'] * 100, 0) }}%</td>
                                <td class="px-4 py-2.5 text-right font-medium text-amber-200 tabular-nums">
                                    {{ $fmt((float) $row['quote'][$cell]) }}{{ $period }}
                                </td>
                                <td class="px-4 py-2.5 text-right text-slate-500 tabular-nums">
                                    {{ $fmt((float) $row['quote']['effective_unit_monthly']) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-admin-layout>
