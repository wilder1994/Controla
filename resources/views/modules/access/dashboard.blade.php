<x-access-layout title="Dashboard de Acceso">
    <div class="-mt-6 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 pt-6 pb-8 bg-gradient-to-r from-slate-800 to-indigo-900 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-indigo-300">Panel de Control</p>
                <h2 class="text-xl font-bold text-white">Dashboard de Acceso</h2>
            </div>
            <div class="hidden sm:flex items-center space-x-2 text-indigo-200 text-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>{{ now()->format('l, d F Y') }}</span>
            </div>
        </div>
    </div>

    @include('modules.access.partials.subnav')

    <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <div class="relative bg-slate-900 rounded-xl border border-slate-800 overflow-hidden group hover:border-slate-700 transition-all duration-200">
            <div class="absolute inset-y-0 left-0 w-1.5 bg-emerald-500"></div>
            <div class="p-5 pl-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Dentro</p>
                        <p class="mt-1 text-2xl font-bold text-white">{{ $activeEntries }}</p>
                    </div>
                    <div class="w-10 h-10 bg-emerald-900/50 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="relative bg-slate-900 rounded-xl border border-slate-800 overflow-hidden group hover:border-slate-700 transition-all duration-200">
            <div class="absolute inset-y-0 left-0 w-1.5 bg-blue-500"></div>
            <div class="p-5 pl-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Hoy</p>
                        <p class="mt-1 text-2xl font-bold text-white">{{ $todayEntries }}</p>
                    </div>
                    <div class="w-10 h-10 bg-blue-900/50 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="relative bg-slate-900 rounded-xl border border-slate-800 overflow-hidden group hover:border-slate-700 transition-all duration-200">
            <div class="absolute inset-y-0 left-0 w-1.5 bg-indigo-500"></div>
            <div class="p-5 pl-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Visitantes</p>
                        <p class="mt-1 text-2xl font-bold text-white">{{ $totalVisitors }}</p>
                    </div>
                    <div class="w-10 h-10 bg-indigo-900/50 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="relative bg-slate-900 rounded-xl border border-slate-800 overflow-hidden group hover:border-slate-700 transition-all duration-200">
            <div class="absolute inset-y-0 left-0 w-1.5 bg-teal-500"></div>
            <div class="p-5 pl-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Residentes</p>
                        <p class="mt-1 text-2xl font-bold text-white">{{ $totalResidents }}</p>
                    </div>
                    <div class="w-10 h-10 bg-teal-900/50 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="relative bg-slate-900 rounded-xl border border-slate-800 overflow-hidden group hover:border-slate-700 transition-all duration-200">
            <div class="absolute inset-y-0 left-0 w-1.5 bg-orange-500"></div>
            <div class="p-5 pl-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Apartamentos</p>
                        <p class="mt-1 text-2xl font-bold text-white">{{ $totalHousingUnits }}</p>
                    </div>
                    <div class="w-10 h-10 bg-orange-900/50 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="relative bg-slate-900 rounded-xl border border-slate-800 overflow-hidden group hover:border-slate-700 transition-all duration-200">
            <div class="absolute inset-y-0 left-0 w-1.5 bg-slate-500"></div>
            <div class="p-5 pl-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Torres</p>
                        <p class="mt-1 text-2xl font-bold text-white">{{ $totalBuildings }}</p>
                    </div>
                    <div class="w-10 h-10 bg-slate-800 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="relative bg-slate-900 rounded-xl border border-slate-800 overflow-hidden group hover:border-slate-700 transition-all duration-200">
            <div class="absolute inset-y-0 left-0 w-1.5 bg-amber-500"></div>
            <div class="p-5 pl-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pendientes</p>
                        <p class="mt-1 text-2xl font-bold text-amber-400">{{ $pendingCorrespondence }}</p>
                    </div>
                    <div class="w-10 h-10 bg-amber-900/50 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="relative bg-slate-900 rounded-xl border border-slate-800 overflow-hidden group hover:border-slate-700 transition-all duration-200">
            <div class="absolute inset-y-0 left-0 w-1.5 bg-purple-500"></div>
            <div class="p-5 pl-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pre-Autorizaciones</p>
                        <p class="mt-1 text-2xl font-bold text-purple-400">{{ $pendingPreAuthorizations }}</p>
                    </div>
                    <div class="w-10 h-10 bg-purple-900/50 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-slate-900 rounded-xl border border-slate-800 p-5">
            <h3 class="text-sm font-semibold text-white mb-1">Accesos diarios (últimos 7 días)</h3>
            <p class="text-xs text-slate-500 mb-4">Movimientos de entrada registrados por día</p>
            <div class="relative h-64 overflow-y-auto">
                <canvas id="dailyChart"></canvas>
            </div>
        </div>
        <div class="bg-slate-900 rounded-xl border border-slate-800 p-5">
            <h3 class="text-sm font-semibold text-white mb-1">Distribución por tipo</h3>
            <p class="text-xs text-slate-500 mb-4">Visitantes peatonales vs vehiculares vs residentes</p>
            <div class="relative h-64 overflow-y-auto">
                <canvas id="typeChart"></canvas>
            </div>
        </div>
        <div class="bg-slate-900 rounded-xl border border-slate-800 p-5 lg:col-span-2">
            <h3 class="text-sm font-semibold text-white mb-1">Actividad hoy por hora</h3>
            <p class="text-xs text-slate-500 mb-4">Distribución horaria de ingresos del día</p>
            <div class="relative h-56 overflow-y-auto">
                <canvas id="hourlyChart"></canvas>
            </div>
        </div>
    </div>

    <div class="mt-8 bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-800">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-white">Accesos Recientes</h3>
                    <p class="text-sm text-slate-500 mt-0.5">Últimos movimientos registrados en el sistema</p>
                </div>
                <a href="{{ route('access.logs.index') }}" class="text-sm font-medium text-indigo-400 hover:text-indigo-300 transition-colors">Ver todos →</a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-800">
                <thead>
                    <tr class="bg-slate-950/60">
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Persona</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Ubicación</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Ingreso</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($recentLogs as $log)
                    <tr class="hover:bg-slate-800/40 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-indigo-700 flex items-center justify-center text-white text-xs font-bold">
                                    {{ strtoupper(substr($log->visitor?->full_name ?? $log->resident?->full_name ?? '?', 0, 2)) }}
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-white">{{ $log->visitor?->full_name ?? $log->resident?->full_name ?? '-' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $log->access_type == 'visitor_vehicle' ? 'bg-cyan-900/30 text-cyan-300 ring-1 ring-cyan-700' : 'bg-blue-900/30 text-blue-300 ring-1 ring-blue-700' }}">
                                {{ $log->access_type == 'visitor_vehicle' ? 'Visit. Vehicular' : 'Visitante' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400">{{ $log->location?->name ?? '-' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400">{{ $log->entry_time->format('d/m/Y H:i') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($log->status === 'active')
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-900/30 text-emerald-300 ring-1 ring-emerald-700">
                                    <span class="w-1.5 h-1.5 bg-emerald-400 rounded-full"></span>
                                    Dentro
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-800 text-slate-400 ring-1 ring-slate-700">
                                    <span class="w-1.5 h-1.5 bg-slate-500 rounded-full"></span>
                                    Salió
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <svg class="mx-auto h-10 w-10 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <p class="mt-2 text-sm text-slate-500">Sin registros recientes</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') return;

    const dailyCtx = document.getElementById('dailyChart');
    if (dailyCtx) {
        new Chart(dailyCtx, {
            type: 'bar',
            data: {
                labels: @json($dailyLabels),
                datasets: [{
                    label: 'Ingresos',
                    data: @json($dailyData),
                    backgroundColor: 'rgba(99, 102, 241, 0.3)',
                    borderColor: 'rgba(99, 102, 241, 1)',
                    borderWidth: 2,
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1, color: '#94a3b8' }, grid: { color: 'rgba(148, 163, 184, 0.1)' } }, x: { ticks: { color: '#94a3b8' }, grid: { display: false } } }
            }
        });
    }

    const typeCtx = document.getElementById('typeChart');
    if (typeCtx) {
        new Chart(typeCtx, {
            type: 'doughnut',
            data: {
                labels: @json($typeLabels),
                datasets: [{
                    data: @json($typeData),
                    backgroundColor: ['rgba(59, 130, 246, 0.8)', 'rgba(6, 182, 212, 0.8)', 'rgba(16, 185, 129, 0.8)'],
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12, color: '#94a3b8' } } }
            }
        });
    }

    const hourlyCtx = document.getElementById('hourlyChart');
    if (hourlyCtx) {
        new Chart(hourlyCtx, {
            type: 'line',
            data: {
                labels: @json($hourlyLabels),
                datasets: [{
                    label: 'Ingresos',
                    data: @json($hourlyData),
                    borderColor: 'rgba(139, 92, 246, 1)',
                    backgroundColor: 'rgba(139, 92, 246, 0.15)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointBackgroundColor: 'rgba(139, 92, 246, 1)',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1, color: '#94a3b8' }, grid: { color: 'rgba(148, 163, 184, 0.1)' } },
                    x: { ticks: { maxTicksLimit: 12, color: '#94a3b8' }, grid: { display: false } }
                }
            }
        });
    }
});
</script>
@endpush

</x-access-layout>
