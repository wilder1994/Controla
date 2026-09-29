@php
    $fmt = fn (float $n) => '$'.number_format($n, 0, ',', '.');
    $period = $cycle->value === 'annual' ? '/año' : '/mes';
    $cell = $cycle->value === 'annual' ? 'price_annual' : 'price_monthly';
    $pct = fn (float $d) => number_format($d * 100, 0).'%';
@endphp

<x-admin-layout title="Tabla de precios">
    <x-slot:actions>
        <x-ui.button
            :variant="$cycle->value === 'monthly' ? 'platform' : 'secondary'"
            :href="route('admin.pricing.edit', ['cycle' => 'monthly'])"
            size="sm">Mensual</x-ui.button>
        <x-ui.button
            :variant="$cycle->value === 'annual' ? 'platform' : 'secondary'"
            :href="route('admin.pricing.edit', ['cycle' => 'annual'])"
            size="sm">Anual (−{{ number_format($annualDiscount * 100, 0) }}%)</x-ui.button>
        @can('platform.companies.manage')
            <x-ui.button type="button" variant="platform" size="sm" onclick="document.getElementById('catalog-modal').showModal()">
                Editar catálogo
            </x-ui.button>
        @endcan
    </x-slot:actions>

    <div class="space-y-4">
        <p class="text-sm text-slate-400">
            Bronce 1–5 · Plata 6–15 · Oro 16–40 · Platino 41–100 instalaciones.
            Precio = unidad × cupo del pack × (1 − descuento). Unidades sueltas van al 100%.
        </p>

        @foreach ([
            ['Accesos', $accessMatrix, 'Instalaciones + portería. Empleados según metal.'],
            ['Supervisión', $supervisionMatrix, 'Campo y APK. Mismos metales. Requiere sentido operativo con Accesos.'],
            ['Indexación (lista)', $indexingMatrix, 'Sola: más cara. Incluye cupo de empleados del metal.'],
            ['Indexación (add-on)', $indexingAddonMatrix, 'Con Accesos o Supervisión: más barata. Solo carpetas.'],
        ] as [$title, $rows, $hint])
            <section class="rounded-lg border border-slate-800 bg-slate-900/80 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-800">
                    <h3 class="text-sm font-semibold text-white">{{ $title }} · {{ $cycle->label() }}</h3>
                    <p class="text-xs text-slate-500">{{ $hint }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-950/60 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-2 text-left font-medium">Plan</th>
                                <th class="px-4 py-2 text-left font-medium">Cupo</th>
                                <th class="px-4 py-2 text-left font-medium">Empleados</th>
                                <th class="px-4 py-2 text-left font-medium">Dto.</th>
                                <th class="px-4 py-2 text-right font-medium">Lista</th>
                                <th class="px-4 py-2 text-right font-medium">Precio</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800">
                            @foreach ($rows as $row)
                                <tr>
                                    <td class="px-4 py-2.5 font-medium text-slate-200">{{ $row['label'] }}</td>
                                    <td class="px-4 py-2.5 text-slate-400">{{ $row['range'] }}</td>
                                    <td class="px-4 py-2.5 text-slate-400 tabular-nums">{{ number_format($row['employees']) }}</td>
                                    <td class="px-4 py-2.5 text-emerald-400">−{{ $pct($row['discount']) }}</td>
                                    <td class="px-4 py-2.5 text-right text-slate-500 tabular-nums">{{ $fmt((float) $row['list_monthly']) }}/mes</td>
                                    <td class="px-4 py-2.5 text-right font-medium text-white tabular-nums">{{ $fmt((float) $row[$cell]) }}{{ $period }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endforeach

        <section class="rounded-lg border border-slate-800 bg-slate-900/80 overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-800">
                <h3 class="text-sm font-semibold text-white">Observatorio · un módulo</h3>
                <p class="text-xs text-slate-500">Solo con Accesos. El precio baja si el metal de Accesos es más alto.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-950/60 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium">Si Accesos es…</th>
                            <th class="px-4 py-2 text-right font-medium">Observatorio</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @foreach ($observatoryMatrix as $row)
                            <tr>
                                <td class="px-4 py-2.5 text-slate-200">{{ $row['label'] }}</td>
                                <td class="px-4 py-2.5 text-right font-medium text-white tabular-nums">{{ $fmt((float) $row[$cell]) }}{{ $period }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    @can('platform.companies.manage')
    <dialog id="catalog-modal" class="w-full max-w-4xl rounded-xl border border-slate-700 bg-slate-950 p-0 text-slate-200 backdrop:bg-slate-950/70">
        <form method="POST" action="{{ route('admin.pricing.update') }}" class="max-h-[90vh] overflow-y-auto p-5 space-y-5">
            @csrf
            @method('PUT')
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-white">Editar catálogo</h2>
                    <p class="text-xs text-slate-500">Unidades al 100%. Descuento por metal. Módulos por producto (igual en Bronce o Platino).</p>
                </div>
                <button type="button" class="ui-chip-link" onclick="document.getElementById('catalog-modal').close()">Cerrar</button>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ([
                    ['access', 'Unidad Accesos'],
                    ['supervision', 'Unidad Supervisión'],
                    ['indexing', 'Unidad Indexación (sola)'],
                    ['indexing_addon', 'Unidad Indexación (add-on)'],
                ] as [$key, $label])
                    <div>
                        <x-ui.label :for="'unit_'.$key">{{ $label }}</x-ui.label>
                        <x-ui.input accent="platform" type="number" step="1000" min="0" :name="'units['.$key.']'" :id="'unit_'.$key"
                            :value="old('units.'.$key, (int) ($catalog['units'][$key] ?? 0))" />
                    </div>
                @endforeach
            </div>

            @foreach ([
                ['access', 'Accesos'],
                ['supervision', 'Supervisión'],
                ['indexing', 'Indexación'],
            ] as [$key, $label])
                <div class="rounded-lg border border-slate-800 p-3 space-y-2">
                    <p class="text-sm font-medium text-white">{{ $label }} · % descuento por plan</p>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        @foreach (['bronce','plata','oro','platino'] as $metal)
                            <div>
                                <x-ui.label :for="'d_'.$key.'_'.$metal">{{ ucfirst($metal) }} %</x-ui.label>
                                <x-ui.input accent="platform" type="number" step="0.5" min="0" max="90"
                                    :name="'discounts['.$key.']['.$metal.']'" :id="'d_'.$key.'_'.$metal"
                                    :value="old('discounts.'.$key.'.'.$metal, round(($catalog['discounts'][$key][$metal] ?? 0) * 100, 2))" />
                            </div>
                        @endforeach
                    </div>
                    <p class="text-xs text-slate-500">Módulos de {{ $label }}</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($moduleOptions as $mod => $modLabel)
                            <label class="inline-flex items-center gap-1.5 text-xs text-slate-300">
                                <input type="checkbox" name="modules[{{ $key }}][]" value="{{ $mod }}"
                                    class="rounded border-slate-600 bg-slate-900"
                                    @checked(in_array($mod, old('modules.'.$key, $catalog['modules'][$key] ?? []), true))>
                                {{ $modLabel }}
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="rounded-lg border border-slate-800 p-3 space-y-2">
                <p class="text-sm font-medium text-white">Observatorio · precio según metal de Accesos</p>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    @foreach (['bronce','plata','oro','platino'] as $metal)
                        <div>
                            <x-ui.label :for="'obs_'.$metal">{{ ucfirst($metal) }}</x-ui.label>
                            <x-ui.input accent="platform" type="number" step="1000" min="0" :name="'observatory['.$metal.']'" :id="'obs_'.$metal"
                                :value="old('observatory.'.$metal, (int) ($catalog['observatory'][$metal] ?? 0))" />
                        </div>
                    @endforeach
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach ($moduleOptions as $mod => $modLabel)
                        <label class="inline-flex items-center gap-1.5 text-xs text-slate-300">
                            <input type="checkbox" name="modules[observatory][]" value="{{ $mod }}"
                                class="rounded border-slate-600 bg-slate-900"
                                @checked(in_array($mod, old('modules.observatory', $catalog['modules']['observatory'] ?? []), true))>
                            {{ $modLabel }}
                        </label>
                    @endforeach
                </div>
            </div>

            <x-ui.button type="submit" variant="platform" size="md" class="w-full">Guardar y recalcular</x-ui.button>
        </form>
    </dialog>
    @if ($errors->any())
        <script>document.getElementById('catalog-modal')?.showModal()</script>
    @endif
    @endcan
</x-admin-layout>
