@php
    use App\Enums\CompanyPackageSku;
    use App\Enums\PackageModality;
    $fmt = fn (float $n) => '$'.number_format($n, 0, ',', '.');
    $unitManual = (float) $settings->unit_price_manual;
    $unitHardware = (float) $settings->unit_price_hardware;
    $discounts = config('tenancy.pricing.volume_discounts', []);
@endphp

@extends('layouts.public')

@section('content')
    <div class="space-y-8">
        <div>
            <p class="text-xs text-cyan-400 uppercase tracking-widest">Comercial</p>
            <h2 class="text-2xl font-bold text-white mt-1">Planes y precios</h2>
            <p class="text-sm text-slate-400 mt-2">
                Elige el cupo de Accesos. Desde 5 clientes puedes mezclar asientos con y sin hardware.
                Supervisión se ofrece aparte (no aplica al paquete de 1).
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <x-ui.button
                :variant="$cycle->value === 'monthly' ? 'platform' : 'secondary'"
                :href="route('planes.index', ['cycle' => 'monthly'])"
                size="sm">Mensual</x-ui.button>
            <x-ui.button
                :variant="$cycle->value === 'annual' ? 'platform' : 'secondary'"
                :href="route('planes.index', ['cycle' => 'annual'])"
                size="sm">Anual (−{{ number_format($annualDiscount * 100, 0) }}%)</x-ui.button>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            @foreach ($matrix as $row)
                @php
                    $size = (int) $row['size'];
                    $manualQuote = $row['manual'];
                    $amountManual = $cycle->value === 'annual' ? $manualQuote['price_annual'] : $manualQuote['price_monthly'];
                    $skuManual = CompanyPackageSku::fromParts($size, PackageModality::Manual);
                    $skuHardware = CompanyPackageSku::fromParts($size, PackageModality::Hardware);
                @endphp
                <div
                    class="rounded-xl border border-slate-800 bg-slate-900/80 p-4 space-y-3"
                    x-data="{
                        size: {{ $size }},
                        hardware: 0,
                        unitM: {{ $unitManual }},
                        unitH: {{ $unitHardware }},
                        discount: {{ (float) $row['volume_discount_pct'] }},
                        annual: {{ $cycle->value === 'annual' ? 'true' : 'false' }},
                        annualPct: {{ $annualDiscount }},
                        sup: '{{ $row['supervision_offer_sku'] ?? '' }}',
                        choices: {{ \Illuminate\Support\Js::from($row['supervision_choices'] ?? []) }},
                        get manual() { return this.size - this.hardware },
                        get list() { return (this.manual * this.unitM) + (this.hardware * this.unitH) },
                        get monthly() { return Math.round(this.list * (1 - this.discount)) },
                        get amount() {
                            if (! this.annual) return this.monthly
                            return Math.round(this.monthly * 12 * (1 - this.annualPct))
                        },
                        get selectedSup() { return this.choices.find(c => c.sku === this.sup) },
                        format(n) { return '$' + Number(n).toLocaleString('es-CO') }
                    }"
                >
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="font-semibold text-white">{{ $size }} {{ $size === 1 ? 'cliente' : 'clientes' }} · Accesos</h3>
                        <span class="text-[10px] uppercase tracking-wide text-emerald-400">−{{ number_format($row['volume_discount_pct'] * 100, 0) }}% vol.</span>
                    </div>

                    <p class="text-2xl font-bold text-white tabular-nums" x-text="format(amount)">{{ $fmt((float) $amountManual) }}</p>
                    <p class="text-xs text-slate-500">{{ $cycle->label() }} · supervisión básica en puesto incluida</p>

                    @if ($row['allows_mix'])
                        <div>
                            <label class="text-xs text-slate-400">Con hardware: <span class="text-white" x-text="hardware"></span> · Sin hardware: <span class="text-white" x-text="manual"></span></label>
                            <input type="range" min="0" max="{{ $size }}" x-model.number="hardware" class="w-full mt-1 accent-cyan-500">
                        </div>
                    @endif

                    @if ($row['allows_supervision'])
                        <div class="rounded-lg border border-amber-800/50 bg-amber-950/20 p-3 space-y-2">
                            <label class="text-xs text-amber-100/80">Supervisión</label>
                            <select x-model="sup" class="w-full h-9 px-3 text-sm rounded-lg border border-amber-800/50 bg-slate-950 text-white">
                                @foreach ($row['supervision_choices'] ?? [] as $choice)
                                    <option value="{{ $choice['sku'] }}" @selected($choice['sku'] === ($row['supervision_offer_sku'] ?? null))>
                                        {{ $choice['label'] }}{{ ! empty($choice['is_offer']) ? ' · oferta' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-sm text-amber-100">
                                <span class="tabular-nums font-semibold" x-text="selectedSup ? format(selectedSup.amount) : '—'"></span>
                                <span class="text-xs text-amber-100/70" x-show="selectedSup && selectedSup.is_offer"> · oferta de este cupo</span>
                            </p>
                            <a
                                class="inline-flex items-center justify-center w-full h-9 px-3 rounded-lg text-sm font-medium bg-amber-600 text-white hover:bg-amber-500"
                                :href="'{{ route('signup.create') }}?cycle={{ $cycle->value }}&sup=' + sup + '&manual=' + manual + '&hardware=' + hardware + '&sku=' + (hardware === size ? '{{ $skuHardware->value }}' : '{{ $skuManual->value }}')"
                            >
                                Contratar Accesos + Supervisión
                            </a>
                        </div>
                    @elseif ($size === 1)
                        <p class="text-xs text-slate-500">Este cupo no incluye Supervisión comercial (GPS / revista en campo).</p>
                    @endif

                    <a
                        class="inline-flex items-center justify-center w-full h-9 px-3 rounded-lg text-sm font-medium bg-slate-800 text-white hover:bg-slate-700"
                        :href="'{{ route('signup.create') }}?cycle={{ $cycle->value }}&manual=' + manual + '&hardware=' + hardware + '&sku=' + (hardware === size ? '{{ $skuHardware->value }}' : '{{ $skuManual->value }}')"
                    >
                        Solo Accesos
                    </a>
                </div>
            @endforeach
        </div>

        <div>
            <h3 class="text-lg font-semibold text-white">Supervisión (catálogo)</h3>
            <p class="text-sm text-slate-400 mt-1">Planes sueltos hasta 100 clientes; ilimitada vale el doble del pack de 100. Requiere Accesos de 5 o más.</p>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($supervisionMatrix as $row)
                @php
                    $quote = $row['quote'];
                    $amount = $cycle->value === 'annual' ? $quote['price_annual'] : $quote['price_monthly'];
                @endphp
                <div class="rounded-xl border border-amber-800/40 bg-slate-900/80 p-4">
                    <h3 class="font-semibold text-white">{{ $row['label'] }}</h3>
                    <p class="text-2xl font-bold text-amber-200 mt-2 tabular-nums">{{ $fmt((float) $amount) }}</p>
                    <p class="text-xs text-slate-500">{{ $cycle->label() }} · desc. volumen {{ number_format($row['volume_discount_pct'] * 100, 0) }}%</p>
                </div>
            @endforeach
        </div>
    </div>
@endsection
