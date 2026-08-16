@can('platform.companies.manage')
    <div
        x-show="reactivateOpen"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        style="display: none;"
    >
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-black/60" @click="reactivateOpen = false"></div>
            <div class="relative w-full max-w-md rounded-xl border border-slate-700 bg-slate-900 p-5" @click.stop>
                <h3 class="text-sm font-semibold text-white">Reactivar membresía</h3>
                <p class="text-sm text-slate-300 mt-3 leading-relaxed">
                    Su membresía se reactivará y seguirán usando el sistema con normalidad
                    @if ($company->package_ends_at)
                        hasta el <strong class="text-white">{{ $company->package_ends_at->format('d/m/Y') }}</strong>
                    @else
                        hasta el fin del periodo contratado
                    @endif.
                </p>
                <p class="text-xs text-slate-500 mt-3 leading-relaxed">
                    Después de la fecha de corte deberán registrar nuevamente el pago del plan vigente
                    ({{ $company->package_sku?->label() ?? 'paquete actual' }}
                    · {{ $company->billing_cycle?->label() ?? 'ciclo actual' }}
                    · ${{ number_format($company->contractedAmount(), 0, ',', '.') }}).
                </p>

                <form method="POST" action="{{ route('admin.companies.membership.undo-cancel', $company) }}" class="mt-5 space-y-3">
                    @csrf
                    <div class="flex flex-col sm:flex-row sm:justify-end gap-2">
                        <x-ui.button type="button" variant="secondary" size="sm" @click="reactivateOpen = false">
                            Volver
                        </x-ui.button>
                        <x-ui.button type="submit" variant="platform" size="sm">
                            Confirmar reactivación
                        </x-ui.button>
                    </div>
                </form>

                <div class="mt-4 pt-4 border-t border-slate-800">
                    <p class="text-xs text-slate-500 mb-2">¿Prefieren otro cupo o ciclo?</p>
                    <x-ui.button
                        type="button"
                        variant="secondary"
                        size="sm"
                        class="w-full sm:w-auto"
                        @click="reactivateOpen = false; changeOpen = true"
                    >
                        Elegir otro plan
                    </x-ui.button>
                </div>
            </div>
        </div>
    </div>
@endcan
