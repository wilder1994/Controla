@can('platform.companies.manage')
    <div
        x-show="cancelOpen"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        style="display: none;"
    >
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-black/60" @click="cancelOpen = false"></div>
            <div class="relative w-full max-w-md rounded-xl border border-slate-700 bg-slate-900 p-5" @click.stop>
                <h3 class="text-sm font-semibold text-white">Cancelar membresía</h3>
                <p class="text-xs text-slate-500 mt-1">
                    Seguirá usando el servicio hasta
                    {{ $company->package_ends_at?->format('d/m/Y') ?? 'el fin del periodo' }}.
                </p>
                <form method="POST" action="{{ route('admin.companies.membership.cancel', $company) }}" class="mt-4 space-y-3">
                    @csrf
                    <input type="hidden" name="action_context" value="cancel" />
                    <div>
                        <x-ui.label for="cancel_reason">Motivo / observaciones</x-ui.label>
                        <textarea
                            id="cancel_reason"
                            name="reason"
                            rows="3"
                            required
                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
                        >{{ old('reason') }}</textarea>
                        <x-ui.field-error name="reason" />
                    </div>
                    <div class="flex justify-end gap-2">
                        <x-ui.button type="button" variant="secondary" size="sm" @click="cancelOpen = false">Volver</x-ui.button>
                        <x-ui.button type="submit" variant="platform" size="sm">Confirmar cancelación</x-ui.button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endcan
