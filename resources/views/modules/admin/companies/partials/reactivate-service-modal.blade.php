@can('platform.companies.manage')
    <div
        x-show="reactivateServiceOpen"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        style="display: none;"
    >
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-black/60" @click="reactivateServiceOpen = false"></div>
            <div class="relative w-full max-w-md rounded-xl border border-slate-700 bg-slate-900 p-5" @click.stop>
                <h3 class="text-sm font-semibold text-white">Reactivar acceso</h3>
                <p class="text-xs text-slate-500 mt-1">
                    Se prende de nuevo el sistema. Todos los usuarios de la empresa podrán entrar. Si estaba archivada, vuelve a cartera.
                </p>
                <form method="POST" action="{{ route('admin.companies.reactivate-service', $company) }}" class="mt-4 flex justify-end gap-2">
                    @csrf
                    <x-ui.button type="button" variant="secondary" size="sm" @click="reactivateServiceOpen = false">Volver</x-ui.button>
                    <x-ui.button type="submit" variant="platform" size="sm">Reactivar</x-ui.button>
                </form>
            </div>
        </div>
    </div>
@endcan
