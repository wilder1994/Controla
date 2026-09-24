@can('platform.companies.manage')
    <div
        x-show="archiveOpen"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        style="display: none;"
    >
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-black/60" @click="archiveOpen = false"></div>
            <div class="relative w-full max-w-md rounded-xl border border-slate-700 bg-slate-900 p-5" @click.stop>
                <h3 class="text-sm font-semibold text-white">Archivar empresa</h3>
                <p class="text-xs text-slate-500 mt-1">
                    Suspende el acceso y saca la empresa de cartera. Úsalo cuando no haya acuerdo comercial.
                </p>
                <form method="POST" action="{{ route('admin.companies.archive', $company) }}" class="mt-4 space-y-3">
                    @csrf
                    <div>
                        <x-ui.label for="archive_reason">Motivo</x-ui.label>
                        <select
                            id="archive_reason"
                            name="archive_reason"
                            required
                            class="w-full h-9 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white"
                        >
                            <option value="cancelled">Baja voluntaria</option>
                            <option value="non_payment">Falta de pago</option>
                        </select>
                        <x-ui.field-error name="archive_reason" />
                    </div>
                    <div class="flex justify-end gap-2">
                        <x-ui.button type="button" variant="secondary" size="sm" @click="archiveOpen = false">Volver</x-ui.button>
                        <x-ui.button type="submit" variant="platform" size="sm">Archivar</x-ui.button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endcan
