<x-access-layout title="Ingreso / Salida">
    <div class="-mt-6 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 pt-6 pb-8 bg-gradient-to-r from-slate-800 to-indigo-900 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-indigo-300">Control de Acceso</p>
                <h2 class="text-xl font-bold text-white">Ingreso / Salida</h2>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('access.logs.entry') }}" class="inline-flex items-center px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold rounded-lg transition-colors">
                    <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    Ingreso
                </a>
                @if($activeLogs->count())
                <form action="{{ route('access.logs.bulk-exit') }}" method="POST" onsubmit="return confirm('¿Marcar salida de todos los registros activos?')">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-red-600 hover:bg-red-500 text-white text-xs font-semibold rounded-lg transition-colors">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        Salida Masiva
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>

    <div x-data="exitModal()">
        @include('modules.access.partials.subnav')

        @if(session('success'))
        <div class="mt-6 rounded-lg bg-emerald-900/40 border border-emerald-700 text-emerald-200 px-4 py-3 text-sm flex items-center gap-2">
            <svg class="w-5 h-5 text-emerald-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('success') }}
        </div>
        @endif
        @if(session('error'))
        <div class="mt-6 rounded-lg bg-red-900/40 border border-red-700 text-red-200 px-4 py-3 text-sm flex items-center gap-2">
            <svg class="w-5 h-5 text-red-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('error') }}
        </div>
        @endif

        @if($activeLogs->count())
        @php $longStayCount = $activeLogs->where('alert_long_stay', true)->count(); @endphp
        @if($longStayCount > 0)
        <div class="mt-4 rounded-lg bg-red-900/40 border border-red-700 text-red-200 px-4 py-3 text-sm flex items-center justify-between gap-3">
            <span>
                <strong>{{ $longStayCount }}</strong> {{ $longStayCount === 1 ? 'persona lleva' : 'personas llevan' }} más de {{ config('access.alerts.long_stay_hours') }} horas dentro del conjunto.
            </span>
            <a href="{{ route('access.logs.exit.page') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-red-100 hover:text-white bg-red-600 hover:bg-red-500 px-3 py-1.5 rounded-lg transition-colors">Registrar salidas →</a>
        </div>
        @endif
        <div class="mt-6 bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-white">Personas Dentro del Edificio</h3>
                    <p class="text-sm text-slate-500 mt-0.5">Registros activos con ingreso registrado</p>
                </div>
                <div class="flex items-center gap-2">
                    @if($longStayCount > 0)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-900/40 text-red-300 ring-1 ring-red-700">
                        {{ $longStayCount }} alertas
                    </span>
                    @endif
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-emerald-900/30 text-emerald-300 ring-1 ring-emerald-700">
                        <span class="w-1.5 h-1.5 bg-emerald-400 rounded-full mr-1.5"></span>
                        <span x-text="activeCount">{{ $activeLogs->count() }}</span> dentro
                    </span>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-800">
                    <thead class="bg-slate-950/60">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Persona</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Tipo</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Documento</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Destino</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Ubicación</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Ingreso</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Tiempo</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-slate-400 uppercase tracking-wider">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @foreach($activeLogs as $log)
                        @php
                            $personName = $log->visitor?->full_name ?? $log->resident?->full_name ?? '-';
                            $personDoc = $log->visitor ? $log->visitor->document_type . ' ' . $log->visitor->document_number : ($log->resident ? $log->resident->document_type . ' ' . $log->resident->document_number : '-');
                            $personType = $log->access_type == 'visitor_vehicle' ? 'Visit. Vehicular' : 'Visitante';
                            $hoursInside = $log->entry_time->diffInHours(now());
                            $destination = $log->housingUnit?->full_label ?? $log->host?->name ?? '-';
                        @endphp
                        <tr class="hover:bg-slate-800/40 transition-colors {{ $log->alert_long_stay ? 'bg-red-900/20' : '' }}">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full {{ $log->alert_long_stay ? 'bg-gradient-to-br from-red-400 to-red-600' : 'bg-gradient-to-br from-indigo-500 to-indigo-700' }} flex items-center justify-center text-white text-xs font-bold">
                                        {{ strtoupper(substr($personName, 0, 2)) }}
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-white">{{ $personName }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ring-1 {{ $personType == 'Visit. Vehicular' ? 'bg-cyan-900/30 text-cyan-300 ring-cyan-700' : 'bg-blue-900/30 text-blue-300 ring-blue-700' }}">
                                    {{ $personType }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400">{{ $personDoc }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400">{{ $destination }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400">{{ $log->location->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400">{{ $log->entry_time->format('H:i') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-1">
                                    <span class="text-sm {{ $log->alert_long_stay ? 'text-red-400 font-semibold' : 'text-slate-400' }}">
                                        {{ $log->entry_time->diffForHumans(now(), true) }}
                                    </span>
                                    @if($log->alert_long_stay)
                                    <svg class="w-4 h-4 text-red-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                <button
                                    @click="openExitModal(
                                        '{{ route('access.logs.exit', $log) }}',
                                        '{{ addslashes($personName) }}',
                                        '{{ addslashes($personDoc) }}',
                                        '{{ addslashes($destination) }}',
                                        '{{ $log->entry_time->format('H:i') }}',
                                        '{{ $log->entry_time->diffForHumans(now(), true) }}',
                                        '{{ strtoupper(substr($personName, 0, 2)) }}',
                                        {{ $log->alert_long_stay ? 'true' : 'false' }}
                                    )"
                                    class="inline-flex items-center px-3 py-1.5 bg-red-600 border border-transparent rounded-lg font-semibold text-xs text-white hover:bg-red-700 transition-colors"
                                >
                                    Registrar Salida
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Exit confirmation modal --}}
        <div
            x-show="open"
            x-cloak
            @keydown.escape="close()"
            class="fixed inset-0 z-50 overflow-y-auto"
            style="display: none;"
        >
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div x-show="open" x-transition.opacity @click="close()" class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity"></div>

                <div x-show="open" x-transition:enter="transition-transform duration-300 ease-out" x-transition:enter-start="scale-95 opacity-0" x-transition:enter-end="scale-100 opacity-100" class="relative bg-slate-900 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden border border-slate-800" @click.stop>
                    <form :action="exitAction" method="POST">
                        @csrf @method('PATCH')

                        {{-- Header --}}
                        <div class="bg-gradient-to-r from-red-700 to-rose-800 px-6 py-5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-full bg-white/10 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-red-200">Registrar Salida</p>
                                        <p class="text-lg font-bold text-white">Confirmar egreso</p>
                                    </div>
                                </div>
                                <button @click="close()" type="button" class="text-red-200 hover:text-white transition-colors">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>

                        {{-- Person info card --}}
                        <div class="px-6 pt-5 pb-3">
                            <div class="bg-slate-950 rounded-xl p-4 border border-slate-800">
                                <div class="flex items-center gap-4">
                                    <div class="w-14 h-14 rounded-full" :class="exitAlert ? 'bg-gradient-to-br from-red-400 to-red-600' : 'bg-gradient-to-br from-indigo-500 to-indigo-700'" flex items-center justify-center text-white text-lg font-bold>
                                        <div class="w-full h-full flex items-center justify-center text-white text-lg font-bold" x-text="exitAvatar"></div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-base font-semibold text-white truncate" x-text="exitName"></p>
                                        <p class="text-sm text-slate-400 truncate" x-text="exitDoc"></p>
                                        <div class="flex items-center gap-3 mt-1">
                                            <span class="text-xs text-slate-500">Destino:</span>
                                            <span class="text-xs font-medium text-slate-300 truncate" x-text="exitDestination"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-3 mt-4 pt-3 border-t border-slate-800">
                                    <div>
                                        <p class="text-xs text-slate-500">Ingreso</p>
                                        <p class="text-sm font-semibold text-white" x-text="exitEntryTime"></p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-500">Tiempo dentro</p>
                                        <p class="text-sm font-semibold" :class="exitAlert ? 'text-red-400' : 'text-white'" x-text="exitDuration"></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Active count badge --}}
                        <div class="px-6 pb-3">
                            <div class="flex items-center gap-2 bg-emerald-900/30 rounded-lg px-4 py-2.5 border border-emerald-800">
                                <span class="w-2 h-2 bg-emerald-400 rounded-full"></span>
                                <span class="text-sm text-emerald-300 font-medium">
                                    Personas dentro del edificio:
                                </span>
                                <span class="ml-auto inline-flex items-center justify-center w-7 h-7 rounded-full bg-emerald-800 text-emerald-200 text-xs font-bold" x-text="activeCount"></span>
                            </div>
                        </div>

                        {{-- Custody section --}}
                        <div class="px-6 pb-3">
                            <div class="bg-amber-900/30 rounded-xl p-4 border border-amber-800">
                                <label class="flex items-start gap-3 cursor-pointer">
                                    <input type="checkbox" x-model="hasCustody" class="mt-0.5 rounded border-amber-600 text-amber-500 focus:ring-amber-500 bg-slate-950">
                                    <div>
                                        <p class="text-sm font-medium text-amber-200">¿Dejó objetos en custodia?</p>
                                        <p class="text-xs text-amber-400">Llaves, documentos, paquetes u otros artículos</p>
                                    </div>
                                </label>

                                <div x-show="hasCustody" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" class="mt-3 space-y-3">
                                    <div>
                                        <label class="block text-xs font-medium text-amber-300 mb-1">Descripción de los objetos</label>
                                        <textarea
                                            name="custody_description"
                                            x-model="custodyDescription"
                                            rows="2"
                                            class="w-full rounded-lg bg-slate-950 border-slate-700 text-white text-sm focus:border-amber-500 focus:ring-amber-500"
                                            placeholder="Ej: Llaves del carro, bulto de ropa..."
                                        ></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-amber-300 mb-1">Recibido por (opcional)</label>
                                        <input
                                            type="text"
                                            name="custody_receiver_name"
                                            x-model="custodyReceiverName"
                                            class="w-full rounded-lg bg-slate-950 border-slate-700 text-white text-sm focus:border-amber-500 focus:ring-amber-500"
                                            placeholder="Nombre de quien recibe"
                                        >
                                    </div>
                                    <input type="hidden" name="has_custody" x-bind:value="hasCustody ? '1' : '0'">
                                </div>
                            </div>
                        </div>

                        {{-- Footer --}}
                        <div class="px-6 py-4 bg-slate-950 border-t border-slate-800 flex items-center justify-end gap-3">
                            <button @click="close()" type="button" class="px-4 py-2 text-sm font-medium text-slate-300 bg-slate-800 border border-slate-700 rounded-lg hover:bg-slate-700 transition-colors">
                                Cancelar
                            </button>
                            <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors inline-flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                Confirmar Salida
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="mt-6 bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-800">
                <h3 class="text-base font-semibold text-white">Registros del Día</h3>
                <p class="text-sm text-slate-500 mt-0.5">Todos los movimientos registrados hoy</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-800">
                    <thead class="bg-slate-950/60">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Persona</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Tipo</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Ingreso</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Salida</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @forelse($todayLogs as $log)
                        @php
                            $personName = $log->visitor?->full_name ?? $log->resident?->full_name ?? '-';
                        @endphp
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-indigo-700 flex items-center justify-center text-white text-xs font-bold">
                                        {{ strtoupper(substr($personName, 0, 2)) }}
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-white">{{ $personName }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ring-1 {{ $log->access_type == 'visitor_vehicle' ? 'bg-cyan-900/30 text-cyan-300 ring-cyan-700' : 'bg-blue-900/30 text-blue-300 ring-blue-700' }}">
                                    {{ $log->access_type == 'visitor_vehicle' ? 'Visit. Vehicular' : 'Visitante' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400">{{ $log->entry_time->format('H:i') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400">{{ $log->exit_time?->format('H:i') ?? '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($log->status == 'active')
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
                                <svg class="mx-auto h-10 w-10 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6"/></svg>
                                <p class="mt-2 text-sm text-slate-500">Sin registros hoy</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-4">{{ $todayLogs->links() }}</div>
    </div>

@push('scripts')
<script>
    function exitModal() {
        return {
            open: false,
            exitAction: '',
            exitName: '',
            exitDoc: '',
            exitDestination: '',
            exitEntryTime: '',
            exitDuration: '',
            exitAvatar: '',
            exitAlert: false,
            activeCount: {{ $activeLogs->count() }},
            hasCustody: false,
            custodyDescription: '',
            custodyReceiverName: '',
            openExitModal(action, name, doc, destination, entryTime, duration, avatar, alert) {
                this.exitAction = action;
                this.exitName = name;
                this.exitDoc = doc;
                this.exitDestination = destination;
                this.exitEntryTime = entryTime;
                this.exitDuration = duration;
                this.exitAvatar = avatar;
                this.exitAlert = alert;
                this.hasCustody = false;
                this.custodyDescription = '';
                this.custodyReceiverName = '';
                this.open = true;
            },
            close() {
                this.open = false;
            }
        }
    }
</script>
@endpush

</x-access-layout>
