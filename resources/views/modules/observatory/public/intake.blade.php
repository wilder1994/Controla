<x-observatory-layout :title="'Reportar · '.$client->name">
    <p class="text-xs uppercase tracking-wider text-slate-500">Observatorio</p>
    <h1 class="text-xl font-semibold text-white mt-1">{{ $client->name }}</h1>
    <p class="text-sm text-slate-400 mt-1">Cuéntanos qué pasó. Puedes hacerlo anónimo.</p>

    @if ($errors->any())
        <div class="mt-4 rounded-lg border border-red-800 bg-red-950/40 px-3 py-2 text-sm text-red-200">
            <ul class="list-disc pl-4 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('observatory.public.store', $client->slug) }}"
        enctype="multipart/form-data"
        class="mt-6 space-y-4"
        x-data="observatoryIntake(@js([
            'sitesUrl' => route('observatory.public.sites', $client->slug),
            'installationId' => (string) old('installation_id', ''),
            'installationName' => old('installation_label', ''),
            'kind' => old('kind', ''),
            'anonymous' => (bool) old('is_anonymous', false),
            'latitude' => old('latitude', ''),
            'longitude' => old('longitude', ''),
            'mapsKey' => $maps['api_key'] ?? '',
            'center' => $maps['center'] ?? ['lat' => 4.5709, 'lng' => -74.2973],
        ]))"
    >
        @csrf
        <input type="hidden" name="installation_id" :value="installationId">
        <input type="hidden" name="installation_label" :value="installationName">
        <input type="hidden" name="latitude" :value="latitude">
        <input type="hidden" name="longitude" :value="longitude">

        <div class="flex gap-2 text-[11px] text-slate-500">
            <span :class="step === 1 ? 'text-teal-300' : ''">1. Colegio</span>
            <span>·</span>
            <span :class="step === 2 ? 'text-teal-300' : ''">2. Qué pasó</span>
            <span>·</span>
            <span :class="step === 3 ? 'text-teal-300' : ''">3. Quién eres</span>
        </div>

        <div x-show="step === 1" class="space-y-3">
            <label class="block text-xs text-slate-400" for="site-q">Colegio</label>
            <input
                id="site-q"
                type="search"
                x-model="query"
                @input.debounce.250ms="search()"
                placeholder="Nombre o código DANE"
                autocomplete="off"
                class="w-full h-11 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white placeholder:text-slate-600"
            >
            <p x-show="installationName" class="text-sm text-teal-300" x-text="'Elegido: ' + installationName"></p>
            <div class="rounded-lg border border-slate-800 divide-y divide-slate-800 overflow-hidden">
                <template x-for="row in sites" :key="row.id">
                    <button type="button" class="block w-full text-left px-3 py-2.5 text-sm text-slate-200 hover:bg-slate-800" @click="pick(row)">
                        <span x-text="row.name"></span>
                        <span class="block text-[11px] text-slate-500" x-text="(row.dane_code ? 'DANE ' + row.dane_code : '') + (row.city ? ' · ' + row.city : '')"></span>
                    </button>
                </template>
                <p x-show="searched && sites.length === 0" class="px-3 py-3 text-xs text-slate-500">No hay colegios con ese nombre o DANE.</p>
            </div>
            <button type="button" class="w-full h-11 rounded-lg bg-teal-600 text-sm font-semibold text-white disabled:opacity-40" :disabled="!installationId" @click="goStep(2)">
                Continuar
            </button>
        </div>

        <div x-show="step === 2" x-cloak class="space-y-3">
            <div>
                <label class="block text-xs text-slate-400 mb-1" for="kind">Tipo</label>
                <select id="kind" name="kind" x-model="kind" required class="w-full h-11 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white">
                    <option value="">Seleccione…</option>
                    @foreach ($kinds as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-400 mb-1" for="body">Qué pasó</label>
                <textarea id="body" name="body" rows="5" required minlength="10" maxlength="2000" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white">{{ old('body') }}</textarea>
            </div>
            <div>
                <label class="block text-xs text-slate-400 mb-1">Dónde pasó</label>
                <div x-show="hasPin && mapsKey" class="overflow-hidden rounded-lg border border-slate-800">
                    <div x-ref="pinMap" class="h-56 w-full"></div>
                    <p class="px-3 py-2 text-[11px] text-slate-500">Arrastra el pin o toca el mapa. Si no lo mueves, queda en el colegio.</p>
                </div>
                <p x-show="!mapsKey || !hasPin" class="text-[11px] text-slate-500">Sin mapa: se usa el pin del colegio si existe.</p>
            </div>
            <div>
                <label class="block text-xs text-slate-400 mb-1" for="photo">Foto (opcional)</label>
                <input id="photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="w-full text-sm text-slate-400">
            </div>
            <div class="flex gap-2">
                <button type="button" class="h-11 px-4 rounded-lg border border-slate-700 text-sm text-slate-300" @click="goStep(1)">Atrás</button>
                <button type="button" class="flex-1 h-11 rounded-lg bg-teal-600 text-sm font-semibold text-white disabled:opacity-40" :disabled="!kind" @click="step = 3">Continuar</button>
            </div>
        </div>

        <div x-show="step === 3" x-cloak class="space-y-3">
            @include('partials.minors-data-notice')
            <label class="flex items-center gap-2 text-sm text-slate-300">
                <input type="hidden" name="is_anonymous" value="0">
                <input type="checkbox" name="is_anonymous" value="1" x-model="anonymous" class="rounded border-slate-600 bg-slate-950 text-teal-600">
                Quiero reportar en anónimo
            </label>
            <div x-show="!anonymous" class="space-y-3">
                <div>
                    <label class="block text-xs text-slate-400 mb-1" for="reporter_name">Nombre</label>
                    <input id="reporter_name" type="text" name="reporter_name" value="{{ old('reporter_name') }}" class="w-full h-11 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white">
                </div>
                <div>
                    <label class="block text-xs text-slate-400 mb-1" for="reporter_phone">Teléfono (opcional)</label>
                    <input id="reporter_phone" type="text" name="reporter_phone" value="{{ old('reporter_phone') }}" class="w-full h-11 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white">
                </div>
            </div>
            <div class="flex gap-2">
                <button type="button" class="h-11 px-4 rounded-lg border border-slate-700 text-sm text-slate-300" @click="goStep(2)">Atrás</button>
                <button type="submit" class="flex-1 h-11 rounded-lg bg-teal-600 text-sm font-semibold text-white">Enviar reporte</button>
            </div>
        </div>
    </form>
</x-observatory-layout>
