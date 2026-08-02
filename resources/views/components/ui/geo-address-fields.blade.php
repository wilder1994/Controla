@props([
    'address' => null,
    'latitude' => null,
    'longitude' => null,
    'accent' => 'default',
])

@php
    $focus = $accent === 'platform'
        ? 'focus:border-violet-500 focus:ring-violet-500/30'
        : 'focus:border-indigo-500 focus:ring-indigo-500/30';
@endphp

<div class="space-y-4">
    <div>
        <x-ui.label for="address">Dirección</x-ui.label>
        <x-ui.input
            id="address"
            name="address"
            :value="old('address', $address)"
            placeholder="Calle, barrio, ciudad"
            :accent="$accent"
        />
        <x-ui.field-error :messages="$errors->get('address')" />
        <p class="mt-1 text-xs text-slate-500">Usada en mapa de plataforma y expediente comercial.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-ui.label for="latitude">Latitud (opcional)</x-ui.label>
            <x-ui.input
                id="latitude"
                type="number"
                step="any"
                name="latitude"
                :value="old('latitude', $latitude)"
                placeholder="4.5709"
                :accent="$accent"
            />
            <x-ui.field-error :messages="$errors->get('latitude')" />
        </div>
        <div>
            <x-ui.label for="longitude">Longitud (opcional)</x-ui.label>
            <x-ui.input
                id="longitude"
                type="number"
                step="any"
                name="longitude"
                :value="old('longitude', $longitude)"
                placeholder="-74.2973"
                :accent="$accent"
            />
            <x-ui.field-error :messages="$errors->get('longitude')" />
        </div>
    </div>
</div>
