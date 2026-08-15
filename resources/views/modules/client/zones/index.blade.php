<x-client-layout title="Zonas comunes">
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-white">Zonas comunes</h2>
                <p class="text-sm text-slate-400 mt-1">Reserva espacios del conjunto para tu unidad.</p>
            </div>
            <a href="{{ route('client.zones.book') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 transition-colors shadow-sm">
                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Reservar zona
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($zones as $zone)
                <div class="rounded-xl border border-slate-800 bg-slate-900 p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-white">{{ $zone->name }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">{{ $zone->typeLabel() }} · Capacidad {{ $zone->capacity }}</p>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2 text-xs text-slate-400">
                        <span>🕐 {{ $zone->open_time?->format('H:i') }} – {{ $zone->close_time?->format('H:i') }}</span>
                        @if($zone->requires_approval)
                            <span class="text-amber-300">Requiere aprobación</span>
                        @endif
                    </div>
                    @if($zone->description)
                        <p class="mt-3 text-xs text-slate-500 line-clamp-2">{{ $zone->description }}</p>
                    @endif
                    <a href="{{ route('client.zones.book', ['zone_id' => $zone->id]) }}" class="mt-4 inline-flex items-center text-xs font-semibold text-indigo-400 hover:text-indigo-300">
                        Reservar →
                    </a>
                </div>
            @empty
                <div class="col-span-full text-center py-10 text-sm text-slate-500">No hay zonas comunes disponibles en este momento.</div>
            @endforelse
        </div>

        <div class="rounded-xl border border-slate-800 bg-slate-900 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-200">Mis reservas</h3>
                <span class="text-xs text-slate-500">{{ $myBookings->count() }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wider text-slate-500 border-b border-slate-800">
                            <th class="px-5 py-3">Zona</th>
                            <th class="px-5 py-3">Fecha</th>
                            <th class="px-5 py-3">Horario</th>
                            <th class="px-5 py-3">Estado</th>
                            <th class="px-5 py-3">QR</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/70">
                        @forelse($myBookings as $booking)
                            <tr class="hover:bg-slate-800/40 transition-colors">
                                <td class="px-5 py-3 text-white font-medium">{{ $booking->zone?->name }}</td>
                                <td class="px-5 py-3 text-slate-300">{{ $booking->date->format('d/m/Y') }}</td>
                                <td class="px-5 py-3 text-slate-300">{{ $booking->start_time->format('H:i') }} – {{ $booking->end_time->format('H:i') }}</td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium
                                        {{ $booking->status === 'pending' ? 'bg-amber-900/50 text-amber-300' : ($booking->status === 'confirmed' ? 'bg-sky-900/50 text-sky-300' : ($booking->status === 'checked_in' ? 'bg-emerald-900/50 text-emerald-300' : 'bg-slate-800 text-slate-400')) }}">
                                        {{ $booking->statusLabel() }}
                                    </span>
                                </td>
                                <td class="px-5 py-3">
                                    @if($booking->qr_code && in_array($booking->status, ['pending', 'confirmed'], true))
                                        <details>
                                            <summary class="cursor-pointer text-xs text-indigo-400 hover:text-indigo-300 font-medium list-none">Ver QR</summary>
                                            <div class="mt-2 inline-block bg-white rounded-lg p-2">
                                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data={{ urlencode($booking->qr_code) }}" alt="QR reserva" class="w-28 h-28">
                                            </div>
                                            <p class="mt-1 text-[10px] text-slate-500 break-all">{{ $booking->qr_code }}</p>
                                        </details>
                                    @else
                                        <span class="text-xs text-slate-600">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3">
                                    @if(! in_array($booking->status, ['completed', 'cancelled', 'checked_in'], true))
                                        <form method="POST" action="{{ route('client.zones.cancel', $booking) }}" onsubmit="return confirm('¿Cancelar esta reserva?');">
                                            @csrf
                                            <button type="submit" class="text-xs font-medium text-red-400 hover:text-red-300">Cancelar</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-8 text-center text-sm text-slate-500">Aún no tienes reservas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-client-layout>