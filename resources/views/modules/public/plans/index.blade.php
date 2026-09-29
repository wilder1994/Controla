@php
    $fmt = fn (float $n) => '$'.number_format($n, 0, ',', '.');
    $cell = $cycle->value === 'annual' ? 'price_annual' : 'price_monthly';
    $period = $cycle->value === 'annual' ? '/año' : '/mes';
@endphp

@extends('layouts.public')

@section('content')
    <div class="space-y-8">
        <div>
            <p class="text-xs text-cyan-400 uppercase tracking-widest">Comercial</p>
            <h2 class="text-2xl font-bold text-white mt-1">Planes Bronce, Plata, Oro y Platino</h2>
            <p class="text-sm text-slate-400 mt-2">
                Compra por unidades o el pack del metal. Si pasas el pack, pagas el paquete más 1 unidad al 100%.
                Supervisión e Indexación se agrupan con Accesos: el sistema te dice qué lleva cada uno.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <x-ui.button :variant="$cycle->value === 'monthly' ? 'platform' : 'secondary'" :href="route('planes.index', ['cycle' => 'monthly'])" size="sm">Mensual</x-ui.button>
            <x-ui.button :variant="$cycle->value === 'annual' ? 'platform' : 'secondary'" :href="route('planes.index', ['cycle' => 'annual'])" size="sm">Anual (−{{ number_format($annualDiscount * 100, 0) }}%)</x-ui.button>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            @foreach ($accessMatrix as $i => $row)
                @php $sku = $signupSku[$row['metal']] ?? 'pack_5_manual'; @endphp
                <article class="rounded-xl border border-slate-800 bg-slate-900/80 p-4 space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="font-semibold text-white">{{ $row['label'] }} · Accesos</h3>
                        <span class="ui-chip ui-chip-success">−{{ number_format($row['discount'] * 100, 0) }}%</span>
                    </div>
                    <p class="text-xs text-slate-500">{{ $row['range'] }} · hasta {{ number_format($row['employees']) }} empleados</p>
                    <p class="text-2xl font-bold text-white tabular-nums">{{ $fmt((float) $row[$cell]) }}<span class="text-sm font-normal text-slate-500">{{ $period }}</span></p>
                    <p class="text-xs text-slate-500">Lista {{ $fmt((float) $row['list_monthly']) }}/mes · ahorras {{ $fmt((float) $row['savings']) }}</p>
                    <ul class="text-xs text-slate-400 space-y-1">
                        <li>Lleva <span class="text-slate-200">Mi empresa, clientes, instalaciones, pánicos, empleados, ajustes</span>.</li>
                        <li>Si sumas Supervisión ({{ $fmt((float) $supervisionMatrix[$i][$cell]) }}{{ $period }}) lleva <span class="text-slate-200">campo, descargas APK</span> y el mismo tope de empleados (el mayor).</li>
                        <li>Indexación add-on {{ $fmt((float) $indexingAddonMatrix[$i][$cell]) }}{{ $period }}: carpetas de esos empleados (más barata que sola {{ $fmt((float) $indexingMatrix[$i][$cell]) }}{{ $period }}).</li>
                        <li>Observatorio {{ $fmt((float) $observatoryMatrix[$i][$cell]) }}{{ $period }} solo si contratas Accesos.</li>
                    </ul>
                    <a class="inline-flex items-center justify-center w-full h-9 px-3 rounded-lg text-sm font-medium bg-cyan-600 text-white hover:bg-cyan-500"
                       href="{{ route('signup.create', ['cycle' => $cycle->value, 'sku' => $sku]) }}">
                        Contratar Accesos {{ $row['label'] }}
                    </a>
                </article>
            @endforeach
        </div>

        <section class="space-y-3">
            <h3 class="text-lg font-semibold text-white">Supervisión</h3>
            <p class="text-sm text-slate-400">Mismos metales. Lleva supervisión, pánicos, descargas, clientes e instalaciones. No incluye Mi empresa.</p>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($supervisionMatrix as $row)
                    <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-3">
                        <p class="font-medium text-white">{{ $row['label'] }}</p>
                        <p class="text-xs text-slate-500">{{ $row['range'] }}</p>
                        <p class="mt-1 font-semibold text-amber-200 tabular-nums">{{ $fmt((float) $row[$cell]) }}{{ $period }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="space-y-3">
            <h3 class="text-lg font-semibold text-white">Indexación</h3>
            <p class="text-sm text-slate-400">Sola = cupo + carpetas. Con Accesos o Supervisión = solo carpetas, más económica.</p>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($indexingMatrix as $i => $row)
                    <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-3">
                        <p class="font-medium text-white">{{ $row['label'] }}</p>
                        <p class="text-xs text-slate-500">Sola {{ $fmt((float) $row[$cell]) }}{{ $period }}</p>
                        <p class="text-xs text-emerald-400">Add-on {{ $fmt((float) $indexingAddonMatrix[$i][$cell]) }}{{ $period }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
@endsection
