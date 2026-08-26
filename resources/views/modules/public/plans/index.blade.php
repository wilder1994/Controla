@php
    use App\Enums\CompanyPackageSku;
    use App\Enums\PackageModality;
    $fmt = fn (float $n) => '$'.number_format($n, 0, ',', '.');
@endphp

@extends('layouts.public')

@section('content')
    <div class="space-y-6">
        <div>
            <p class="text-xs text-cyan-400 uppercase tracking-widest">Comercial</p>
            <h2 class="text-2xl font-bold text-white mt-1">Planes y precios</h2>
            <p class="text-sm text-slate-400 mt-2">Fichas de cliente ilimitadas. El cupo aplica al operar Accesos o Supervisión Pro. Incluida supervisión básica en puesto con Accesos.</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <x-ui.button
                :variant="$cycle->value === 'monthly' ? 'platform' : 'secondary'"
                :href="route('planes.index', ['cycle' => 'monthly', 'modality' => $modality->value])"
                size="sm">Mensual</x-ui.button>
            <x-ui.button
                :variant="$cycle->value === 'annual' ? 'platform' : 'secondary'"
                :href="route('planes.index', ['cycle' => 'annual', 'modality' => $modality->value])"
                size="sm">Anual (−{{ number_format($annualDiscount * 100, 0) }}%)</x-ui.button>
            <x-ui.button
                :variant="$modality === PackageModality::Manual ? 'platform' : 'secondary'"
                :href="route('planes.index', ['cycle' => $cycle->value, 'modality' => 'manual'])"
                size="sm">Manual</x-ui.button>
            <x-ui.button
                :variant="$modality === PackageModality::Hardware ? 'platform' : 'secondary'"
                :href="route('planes.index', ['cycle' => $cycle->value, 'modality' => 'hardware'])"
                size="sm">Con hardware</x-ui.button>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ($matrix as $row)
                @php
                    $quote = $modality === PackageModality::Manual ? $row['manual'] : $row['hardware'];
                    $sku = CompanyPackageSku::fromParts((int) $row['size'], $modality);
                    $amount = $cycle->value === 'annual' ? $quote['price_annual'] : $quote['price_monthly'];
                @endphp
                <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="font-semibold text-white">{{ $row['size'] }} {{ $row['size'] === 1 ? 'conjunto' : 'conjuntos' }}</h3>
                        @if ($row['size'] === 5)
                            <span class="text-[10px] uppercase tracking-wide text-cyan-400">Popular</span>
                        @endif
                    </div>
                    <p class="text-2xl font-bold text-white mt-2 tabular-nums">{{ $fmt((float) $amount) }}</p>
                    <p class="text-xs text-slate-500">{{ $cycle->label() }} · {{ $modality->label() }}</p>
                    <p class="text-xs text-emerald-400/80 mt-1">Incluida supervisión básica en puesto</p>
                    <p class="text-xs text-slate-500 mt-2">Desc. volumen {{ number_format($row['volume_discount_pct'] * 100, 0) }}%</p>
                    <x-ui.button
                        class="mt-4 w-full"
                        :href="route('signup.create', ['sku' => $sku->value, 'cycle' => $cycle->value])">
                        Contratar este plan
                    </x-ui.button>
                </div>
            @endforeach
        </div>

        <div>
            <h3 class="text-lg font-semibold text-white">Supervisión Pro</h3>
            <p class="text-sm text-slate-400 mt-1">GPS y revista en campo. Elige el cupo Accesos y el de Pro por separado.</p>
            <form method="GET" action="{{ route('planes.index') }}" class="mt-3 flex flex-wrap items-end gap-2">
                <input type="hidden" name="cycle" value="{{ $cycle->value }}">
                <input type="hidden" name="modality" value="{{ $modality->value }}">
                <div>
                    <label for="sku" class="text-xs text-slate-500">Accesos a combinar</label>
                    <select id="sku" name="sku" class="mt-1 h-9 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white" onchange="this.form.submit()">
                        @foreach ($matrix as $row)
                            @php $optSku = CompanyPackageSku::fromParts((int) $row['size'], $modality); @endphp
                            <option value="{{ $optSku->value }}" @selected(($comboSku ?? null)?->value === $optSku->value)>{{ $optSku->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ($supervisionMatrix as $row)
                @php
                    $quote = $row['quote'];
                    $supSku = \App\Enums\SupervisionPackageSku::fromSize((int) $row['size']);
                    $amount = $cycle->value === 'annual' ? $quote['price_annual'] : $quote['price_monthly'];
                    $accessSku = $comboSku ?? CompanyPackageSku::fromParts((int) $row['size'], $modality);
                @endphp
                <div class="rounded-xl border border-amber-800/40 bg-slate-900/80 p-4">
                    <h3 class="font-semibold text-white">{{ $supSku->label() }}</h3>
                    <p class="text-2xl font-bold text-amber-200 mt-2 tabular-nums">{{ $fmt((float) $amount) }}</p>
                    <p class="text-xs text-slate-500">{{ $cycle->label() }} · se suma a {{ $accessSku->label() }}</p>
                    <x-ui.button
                        class="mt-4 w-full"
                        :href="route('signup.create', ['sku' => $accessSku->value, 'cycle' => $cycle->value, 'sup' => $supSku->value])">
                        Contratar Accesos + Pro
                    </x-ui.button>
                </div>
            @endforeach
        </div>
    </div>
@endsection
