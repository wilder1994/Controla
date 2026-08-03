<x-company-layout title="Nuevo cliente">
    <div class="max-w-2xl">
        <a href="{{ route('company.clients.index') }}" class="text-sm text-slate-400 hover:text-white">&larr; Volver al listado</a>
        <p class="mt-4 text-sm text-slate-400">
            Define el conjunto y sufijo de login.
            Cupo restante: {{ $metrics['clients_remaining'] }} de {{ $metrics['max_clients'] }}
            ({{ $metrics['package_modality_label'] }}). Portafolio del conjunto sin límite.
        </p>

        <form method="POST" action="{{ route('company.clients.store') }}" class="mt-6 space-y-4 rounded-lg border border-slate-800 bg-slate-900/80 p-4">
            @csrf

            <div>
                <x-ui.label for="name">Nombre del conjunto</x-ui.label>
                <x-ui.input id="name" type="text" name="name" :value="old('name')" required />
                <x-ui.field-error :messages="$errors->get('name')" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-ui.label for="slug">Slug interno</x-ui.label>
                    <x-ui.input id="slug" type="text" name="slug" :value="old('slug')" required placeholder="palmas-del-ingenio" />
                    <x-ui.field-error :messages="$errors->get('slug')" />
                </div>
                <div>
                    <x-ui.label for="login_suffix">Sufijo login APP</x-ui.label>
                    <div class="flex h-9 rounded-lg border border-slate-700 bg-slate-950 overflow-hidden focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500/30">
                        <span class="px-3 text-sm text-slate-500 border-r border-slate-700 inline-flex items-center">usuario@</span>
                        <input type="text" id="login_suffix" name="login_suffix" value="{{ old('login_suffix') }}" required placeholder="palmasdelingenio"
                               class="flex-1 bg-transparent px-3 text-sm text-white focus:outline-none">
                    </div>
                    <x-ui.field-error :messages="$errors->get('login_suffix')" />
                </div>
            </div>

            <x-ui.geo-address-fields
                :address="old('address')"
                :city="old('city')"
                :department="old('department')"
                :latitude="old('latitude')"
                :longitude="old('longitude')"
            />

            <div>
                <x-ui.label for="access_url">URL acceso (opcional)</x-ui.label>
                <x-ui.input id="access_url" type="url" name="access_url" :value="old('access_url')" />
            </div>

            <label class="inline-flex items-center gap-2 text-sm text-slate-300">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-600 bg-slate-950 text-indigo-600">
                Cliente activo
            </label>

            <div class="pt-2">
                <x-ui.button type="submit" size="md">Crear cliente</x-ui.button>
            </div>
        </form>
    </div>
</x-company-layout>
