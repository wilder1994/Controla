<x-company-layout :title="'Editar: '.$client->name">
    <div class="max-w-2xl">
        <a href="{{ route('company.clients.show', $client) }}" class="text-sm text-slate-400 hover:text-white">&larr; Detalle cliente</a>

        <form method="POST" action="{{ route('company.clients.update', $client) }}" class="mt-6 space-y-4 rounded-lg border border-slate-800 bg-slate-900/80 p-4">
            @csrf
            @method('PUT')

            <div>
                <x-ui.label for="name">Nombre</x-ui.label>
                <x-ui.input id="name" type="text" name="name" :value="old('name', $client->name)" required />
                <x-ui.field-error :messages="$errors->get('name')" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-ui.label for="slug">Slug</x-ui.label>
                    <x-ui.input id="slug" type="text" name="slug" :value="old('slug', $client->slug)" required />
                    <x-ui.field-error :messages="$errors->get('slug')" />
                </div>
                <div>
                    <x-ui.label for="login_suffix">Sufijo login</x-ui.label>
                    <x-ui.input id="login_suffix" type="text" name="login_suffix" :value="old('login_suffix', $client->login_suffix)" required />
                    <x-ui.field-error :messages="$errors->get('login_suffix')" />
                </div>
            </div>

            <x-ui.geo-address-fields
                :address="old('address', $client->address)"
                :city="old('city', $client->city)"
                :department="old('department', $client->department)"
                :latitude="old('latitude', $client->latitude)"
                :longitude="old('longitude', $client->longitude)"
            />

            <div>
                <x-ui.label for="access_url">URL acceso</x-ui.label>
                <x-ui.input id="access_url" type="url" name="access_url" :value="old('access_url', $client->access_url)" />
            </div>

            <p class="text-xs text-slate-500">
                Modalidad del conjunto: {{ $client->securityCompany?->package_modality?->label() ?? '—' }}
                (heredada del paquete de la empresa). Portafolio sin límite de unidades.
            </p>

            <label class="inline-flex items-center gap-2 text-sm text-slate-300">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $client->is_active)) class="rounded border-slate-600 bg-slate-950 text-indigo-600">
                Cliente activo
            </label>

            <x-ui.button type="submit" size="md">Guardar cambios</x-ui.button>
        </form>
    </div>
</x-company-layout>
