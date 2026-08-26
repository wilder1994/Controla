<x-company-layout :title="$client->name">
    @include('modules.company.clients.partials.nav-slots', [
        'client' => $client,
        'clientNavActive' => 'cliente',
        'vista' => $vista ?? 'cliente',
        'canOperate' => $canOperate,
        'canOperateClientPanel' => $canOperateClientPanel,
        'canUpdate' => $canUpdate,
        'companyContext' => $companyContext ?? ['is_quota_full' => true],
    ])

    @if (($vista ?? 'cliente') === 'cliente')
        <div class="max-w-3xl space-y-4">
            <section class="rounded-lg border border-slate-800 bg-slate-900/80 p-4 space-y-3">
                <h3 class="text-sm font-semibold text-white">Ficha comercial</h3>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                    <div>
                        <dt class="text-xs text-slate-500">Nombre comercial</dt>
                        <dd class="text-white font-medium">{{ $client->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Razón social</dt>
                        <dd class="text-slate-200">{{ $client->legal_name ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Documento</dt>
                        <dd class="text-slate-200">{{ $client->document_type }} {{ $client->tax_id }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Contacto</dt>
                        <dd class="text-slate-200">{{ $client->email ?: '—' }} · {{ $client->phone ?: '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs text-slate-500">Dirección</dt>
                        <dd class="text-slate-200">{{ $client->address ?: '—' }}{{ $client->city ? ' · '.$client->city : '' }}</dd>
                    </div>
                </dl>
            </section>
            <section class="rounded-lg border border-slate-800 bg-slate-900/80 p-4 space-y-2">
                <h3 class="text-sm font-semibold text-white">Líneas de servicio</h3>
                <p class="text-sm text-slate-400">
                    @if ($client->has_access)
                        Accesos activo (portería y censo).
                    @else
                        Sin Accesos: no se opera portería.
                    @endif
                    @if ($client->has_supervision)
                        Supervisión Pro activa: la revista se firma en la app y llena la minuta del puesto.
                    @else
                        Sin Supervisión Pro.
                    @endif
                </p>
                @if ($client->isCatalogOnly())
                    <p class="text-xs text-slate-500">Esta ficha no consume cupo. Activa Accesos o Pro en Editar cuando tengas asientos.</p>
                @endif
            </section>
        </div>
    @elseif (($vista ?? '') === 'supervision')
        <div class="max-w-3xl space-y-4">
            <section class="rounded-lg border border-amber-800/40 bg-amber-950/10 p-4">
                <h3 class="text-sm font-semibold text-white">Supervisión Pro</h3>
                <p class="mt-1 text-sm text-slate-400">
                    La revista de este sitio se hace en la app Pro. No se vuelve a firmar en portería: Pro llena la misma minuta.
                </p>
            </section>
            <section class="rounded-lg border border-slate-800 bg-slate-900/80 p-4">
                <h3 class="text-sm font-semibold text-white">Revistas recientes</h3>
                <ul class="mt-3 space-y-2">
                    @forelse ($proReviews as $review)
                        <li class="rounded-lg border border-slate-800 bg-slate-950/40 px-3 py-2 text-sm">
                            <p class="text-slate-200">{{ $review->shift?->user?->name ?? 'Supervisor' }}</p>
                            <p class="text-xs text-slate-500">{{ $review->recorded_at?->format('d/m/Y H:i') }} · {{ $review->notes ?: 'Sin notas' }}</p>
                        </li>
                    @empty
                        <li class="text-sm text-slate-500">Aún no hay revistas Pro en este sitio.</li>
                    @endforelse
                </ul>
            </section>
        </div>
    @elseif (($vista ?? '') === 'accesos' && $expediente)
    <div class="space-y-4">
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
            <div class="rounded-lg border border-slate-800 bg-slate-900/80 px-3 py-3">
                <p class="text-[10px] uppercase tracking-wide text-slate-500">Unidades</p>
                <p class="mt-1 text-lg font-semibold text-white tabular-nums">{{ $expediente['structures_total'] }}</p>
                <p class="text-[10px] text-slate-600 mt-1 leading-snug">
                    @forelse ($expediente['structures_breakdown'] as $row)
                        {{ $row['label'] }} {{ $row['count'] }}{{ ! $loop->last ? ' · ' : '' }}
                    @empty
                        Sin unidades de censo
                    @endforelse
                </p>
            </div>
            <div class="rounded-lg border border-slate-800 bg-slate-900/80 px-3 py-3">
                <p class="text-[10px] uppercase tracking-wide text-slate-500">Personas (censo)</p>
                <p class="mt-1 text-lg font-semibold text-white tabular-nums">{{ $expediente['members_total'] }}</p>
                <p class="text-[10px] text-slate-600 mt-1 leading-snug">
                    @forelse ($expediente['members_breakdown'] as $row)
                        {{ $row['label'] }} {{ $row['count'] }}{{ ! $loop->last ? ' · ' : '' }}
                    @empty
                        Sin personas
                    @endforelse
                </p>
            </div>
            <div class="rounded-lg border border-slate-800 bg-slate-900/80 px-3 py-3">
                <p class="text-[10px] uppercase tracking-wide text-slate-500">Usuarios app</p>
                <p class="mt-1 text-lg font-semibold text-white tabular-nums">{{ $expediente['app_users_count'] }}</p>
            </div>
            <div class="rounded-lg border border-slate-800 bg-slate-900/80 px-3 py-3">
                <p class="text-[10px] uppercase tracking-wide text-slate-500">Mascotas</p>
                <p class="mt-1 text-lg font-semibold text-white tabular-nums">{{ $expediente['pets_count'] }}</p>
            </div>
            <div class="rounded-lg border border-slate-800 bg-slate-900/80 px-3 py-3">
                <p class="text-[10px] uppercase tracking-wide text-slate-500">Puntos de acceso</p>
                <p class="mt-1 text-lg font-semibold text-white tabular-nums">{{ $expediente['access_points_count'] }}</p>
            </div>
            <div class="rounded-lg border border-slate-800 bg-slate-900/80 px-3 py-3">
                <p class="text-[10px] uppercase tracking-wide text-slate-500">Bloqueados</p>
                <p class="mt-1 text-lg font-semibold {{ $expediente['blocklist_active'] > 0 ? 'text-rose-300' : 'text-white' }} tabular-nums">
                    {{ $expediente['blocklist_active'] }}
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <section class="rounded-lg border border-slate-800 bg-slate-900/80 p-4 space-y-3">
                <h3 class="text-sm font-semibold text-white">Operación de hoy</h3>
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-lg bg-slate-950/50 border border-slate-800 px-2 py-3">
                        <p class="text-[10px] text-slate-500 uppercase">Entraron</p>
                        <p class="text-xl font-semibold text-emerald-300 tabular-nums">{{ $expediente['entries_today'] }}</p>
                    </div>
                    <div class="rounded-lg bg-slate-950/50 border border-slate-800 px-2 py-3">
                        <p class="text-[10px] text-slate-500 uppercase">Salieron</p>
                        <p class="text-xl font-semibold text-amber-300 tabular-nums">{{ $expediente['exits_today'] }}</p>
                    </div>
                    <div class="rounded-lg bg-slate-950/50 border border-slate-800 px-2 py-3">
                        <p class="text-[10px] text-slate-500 uppercase">Adentro</p>
                        <p class="text-xl font-semibold text-white tabular-nums">{{ $expediente['inside_now'] }}</p>
                    </div>
                </div>
                <div class="h-40">
                    <canvas id="clientPresenceChart" aria-label="Presencia hoy"></canvas>
                </div>
            </section>

            <section class="rounded-lg border border-slate-800 bg-slate-900/80 p-4 space-y-3 lg:col-span-2">
                <h3 class="text-sm font-semibold text-white">Ingresos y salidas (14 días)</h3>
                <div class="h-48">
                    <canvas id="clientTrafficChart" aria-label="Tráfico 14 días"></canvas>
                </div>
            </section>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <section class="rounded-lg border border-slate-800 bg-slate-900/80 p-4 space-y-3">
                <h3 class="text-sm font-semibold text-white">Parque vehicular</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Vehículos residentes</dt>
                        <dd class="text-white tabular-nums font-medium">{{ $expediente['resident_vehicles_registered'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Residentes adentro</dt>
                        <dd class="text-emerald-300 tabular-nums">{{ $expediente['resident_vehicles_inside'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Residentes afuera</dt>
                        <dd class="text-slate-300 tabular-nums">{{ $expediente['resident_vehicles_outside'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-3 pt-2 border-t border-slate-800">
                        <dt class="text-slate-500">Entradas hoy (res.)</dt>
                        <dd class="text-slate-200 tabular-nums">{{ $expediente['resident_vehicle_entries_today'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Salidas hoy (res.)</dt>
                        <dd class="text-slate-200 tabular-nums">{{ $expediente['resident_vehicle_exits_today'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-3 pt-2 border-t border-slate-800">
                        <dt class="text-slate-500">Vehículos visitante (reg.)</dt>
                        <dd class="text-white tabular-nums">{{ $expediente['visitor_vehicles_registered'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Visitantes adentro</dt>
                        <dd class="text-indigo-300 tabular-nums">{{ $expediente['visitor_vehicles_inside'] }}</dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-lg border border-slate-800 bg-slate-900/80 p-4 space-y-3">
                <h3 class="text-sm font-semibold text-white">Padrones registrados</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Personas visitantes</dt>
                        <dd class="text-white tabular-nums font-medium">{{ $expediente['visitors_registered'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Vehículos visitante</dt>
                        <dd class="text-white tabular-nums font-medium">{{ $expediente['visitor_vehicles_registered'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Usuarios app activos</dt>
                        <dd class="text-white tabular-nums font-medium">{{ $expediente['app_users_count'] }}</dd>
                    </div>
                </dl>
                <div class="pt-2 border-t border-slate-800 text-xs text-slate-500 leading-relaxed">
                    Login residentes: <span class="font-mono text-indigo-300">usuario{{ $client->loginDomain() }}</span>
                    · Servicio desde {{ $client->service_started_at?->format('d/m/Y') ?: '—' }}
                </div>
            </section>

            <section class="rounded-lg border border-slate-800 bg-slate-900/80 p-4 space-y-3">
                <h3 class="text-sm font-semibold text-white">Guardas asignados</h3>
                <ul class="space-y-2 max-h-48 overflow-y-auto">
                    @forelse ($expediente['guards'] as $guard)
                        <li class="rounded-lg border border-slate-800 bg-slate-950/40 px-3 py-2">
                            <p class="text-sm text-slate-200">{{ $guard->name }}</p>
                            <p class="text-xs text-slate-500 truncate">{{ $guard->email }}</p>
                        </li>
                    @empty
                        <li class="text-sm text-slate-500">Sin guardas asignados.</li>
                    @endforelse
                </ul>
                @if ($expediente['staff']->isNotEmpty())
                    <div class="pt-2 border-t border-slate-800">
                        <p class="text-xs text-slate-500 mb-1">Otro staff</p>
                        @foreach ($expediente['staff'] as $member)
                            <p class="text-xs text-slate-400 truncate">{{ $member->name }}</p>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        <section class="rounded-lg border border-slate-800 bg-slate-900/80 p-4">
            <h3 class="text-sm font-semibold text-white">Últimos movimientos</h3>
            <div class="mt-3 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="pb-2 text-left font-medium">Quién</th>
                            <th class="pb-2 text-left font-medium">Vehículo</th>
                            <th class="pb-2 text-left font-medium">Entrada</th>
                            <th class="pb-2 text-left font-medium">Salida</th>
                            <th class="pb-2 text-left font-medium">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @forelse ($expediente['recent_entries'] as $log)
                            @php
                                $who = $log->visitor?->full_name
                                    ?? trim(($log->resident?->first_name ?? '').' '.($log->resident?->last_name ?? ''))
                                    ?: 'Registro #'.$log->id;
                            @endphp
                            <tr>
                                <td class="py-2 text-slate-200">{{ $who }}</td>
                                <td class="py-2 text-slate-400 font-mono text-xs">
                                    {{ $log->vehicle?->plate ?? '—' }}
                                    @if ($log->vehicle)
                                        <span class="text-slate-600">
                                            · {{ $log->vehicle->is_visitor_vehicle ? 'visitante' : 'residente' }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-2 text-slate-400 text-xs">{{ $log->entry_time?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="py-2 text-slate-400 text-xs">{{ $log->exit_time?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="py-2 text-xs {{ $log->exit_time ? 'text-slate-500' : 'text-emerald-400' }}">
                                    {{ $log->exit_time ? 'Salió' : 'En sitio' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-6 text-center text-slate-500">Sin movimientos registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
    @endif

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
        <script>
            (function () {
                const expediente = @json($expediente);
                if (! expediente) {
                    return;
                }
                const chart = expediente.chart;
                const presence = expediente.presence_chart;
                const defaults = {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { labels: { color: '#94a3b8', boxWidth: 10, font: { size: 10 } } } },
                    scales: {
                        x: { ticks: { color: '#64748b', font: { size: 10 } }, grid: { color: 'rgba(51,65,85,0.35)' } },
                        y: { ticks: { color: '#64748b', font: { size: 10 } }, grid: { color: 'rgba(51,65,85,0.35)' }, beginAtZero: true },
                    },
                };

                const trafficEl = document.getElementById('clientTrafficChart');
                if (trafficEl) {
                    new Chart(trafficEl, {
                        type: 'line',
                        data: {
                            labels: chart.labels,
                            datasets: [
                                {
                                    label: 'Ingresos',
                                    data: chart.entries,
                                    borderColor: '#34d399',
                                    backgroundColor: 'rgba(52,211,153,0.12)',
                                    fill: true,
                                    tension: 0.35,
                                },
                                {
                                    label: 'Salidas',
                                    data: chart.exits,
                                    borderColor: '#fbbf24',
                                    backgroundColor: 'transparent',
                                    tension: 0.35,
                                },
                            ],
                        },
                        options: defaults,
                    });
                }

                const presenceEl = document.getElementById('clientPresenceChart');
                if (presenceEl) {
                    new Chart(presenceEl, {
                        type: 'doughnut',
                        data: {
                            labels: presence.labels,
                            datasets: [{
                                data: presence.values,
                                backgroundColor: ['#6366f1', '#fbbf24', '#f87171'],
                                borderWidth: 0,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '58%',
                            plugins: { legend: { position: 'bottom', labels: { color: '#94a3b8', boxWidth: 8, font: { size: 10 } } } },
                        },
                    });
                }
            })();
        </script>
    @endpush
</x-company-layout>
