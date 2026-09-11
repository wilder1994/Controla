<x-admin-layout :title="'Primer administrador · '.$company->displayName()">
    <div
        class="max-w-4xl space-y-4"
        x-data="firstAdminAccess({
            previewUrl: @js(route('admin.companies.first-admin.preview', $company)),
            username: @js(old('username', '')),
            password: @js(old('password', '')),
        })"
    >
        <x-ui.button variant="secondary" :href="route('admin.companies.index')" size="sm">← Empresas</x-ui.button>

        <div class="rounded-lg border border-violet-500/40 bg-violet-950/40 px-4 py-3 space-y-1">
            <p class="text-sm font-semibold text-violet-100">Crea el primer usuario para administrar esta empresa.</p>
            <p class="text-sm text-violet-200/90">Misma ficha de empleado. El acceso se genera solo con nombres y apellidos. Rol fijo: Administrador empresa.</p>
        </div>

        <form method="POST" action="{{ route('admin.companies.first-admin.store', $company) }}" enctype="multipart/form-data" class="space-y-5 rounded-lg border border-slate-800 bg-slate-900/80 p-4">
            @csrf

            <div class="space-y-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Empleado</p>
                @include('modules.company.employees.partials.form-fields')
            </div>

            <div class="border-t border-slate-800 pt-4 space-y-3">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Acceso</p>
                <p class="text-sm text-slate-400">Se arma solo: <span class="text-slate-300">nombre.apellido.####</span> y una clave temporal. En el primer ingreso debe cambiarla.</p>

                <input type="hidden" name="username" :value="username">
                <input type="hidden" name="password" :value="password">

                <div class="rounded-lg border border-slate-800 bg-slate-950/50 p-4 space-y-2">
                    <p x-show="loading" class="text-sm text-slate-400">Generando acceso…</p>
                    <p x-show="!loading && !username" class="text-sm text-slate-500">Escribe nombres y al menos un apellido para ver usuario y clave.</p>
                    <div x-show="!loading && username" class="space-y-2">
                        <p class="text-sm text-slate-400">Usuario: <span class="font-mono text-white" x-text="username"></span></p>
                        <p class="text-sm text-slate-400">Contraseña temporal: <span class="font-mono text-white" x-text="password"></span></p>
                    </div>
                </div>
                <x-ui.field-error :messages="$errors->get('username')" />
                <x-ui.field-error :messages="$errors->get('password')" />

                <div class="flex items-center gap-3 pt-2">
                    <x-ui.button type="submit" variant="platform" x-bind:disabled="!username || !password">Crear administrador</x-ui.button>
                </div>
            </div>
        </form>
    </div>
</x-admin-layout>
