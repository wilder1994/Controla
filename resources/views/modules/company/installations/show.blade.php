<x-company-layout :title="$installation->name">
    <x-slot:actions>
        <x-ui.button variant="secondary" :href="route('company.installations.index')" size="sm">← Listado</x-ui.button>
        @can('update', $installation->client)
            <x-ui.button :href="route('company.installations.edit', $installation)" size="sm">Editar</x-ui.button>
        @endcan
    </x-slot:actions>

    <div class="space-y-4">
        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-stretch">
            <div class="min-w-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-900">
                @if ($installation->hasCoordinates() && $maps['api_key'])
                    <div id="installation-pin-map" class="h-96 w-full"></div>
                @elseif ($installation->hasCoordinates())
                    <p class="p-4 text-xs text-slate-500">
                        Pin {{ $installation->latitude }}, {{ $installation->longitude }}. Configura <code class="text-indigo-300">GOOGLE_MAPS_API_KEY</code> para ver el mapa.
                    </p>
                @else
                    <p class="p-4 text-sm text-slate-500">Esta instalación no tiene pin.</p>
                @endif
            </div>
            <div class="rounded-lg border border-slate-800 bg-slate-900/80 p-4 space-y-3">
                <div>
                    <p class="text-xs text-slate-500">Instalación</p>
                    <h3 class="text-lg font-semibold text-white">{{ $installation->name }}</h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        {{ $installation->client?->name }}
                        @if ($installation->is_client_site)
                            · Mismo cliente
                        @endif
                        · {{ $installation->is_active ? 'Activa' : 'Inactiva' }}
                    </p>
                </div>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs text-slate-500">Código</dt>
                        <dd class="font-mono text-indigo-300">{{ $installation->code ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Comuna</dt>
                        <dd class="text-slate-200">{{ $installation->commune ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Admin de sede</dt>
                        <dd class="text-slate-200">{{ $installation->siteAdminLabel() }}</dd>
                    </div>
                </dl>
                @if ($installation->address || $installation->city)
                    <p class="text-xs text-slate-500">
                        {{ $installation->address }}
                        @if ($installation->city)
                            · {{ $installation->city }}{{ $installation->department ? ', '.$installation->department : '' }}
                        @endif
                    </p>
                @endif
            </div>
        </div>

        @if ($installation->client?->has_access || $installation->client?->has_supervision)
            <div class="rounded-lg border border-slate-800 bg-slate-900/80 p-4 space-y-3">
                <div>
                    <p class="text-sm font-medium text-white">Puestos</p>
                    <p class="text-xs text-slate-500">Modalidad y vigilantes de la empresa. Un puesto no es una puerta.</p>
                </div>
                @include('modules.company.clients.partials.posts-block', [
                    'client' => $installation->client,
                    'installation' => $installation,
                    'installations' => collect([$installation]),
                    'vista' => 'sitio',
                    'accent' => 'indigo',
                    'canManageTree' => $canManageTree,
                    'postModalities' => $postModalities,
                    'returnTo' => 'installation',
                ])
            </div>
        @endif

        <p class="text-xs text-slate-500">
            Puertas se gestionan en la
            <a href="{{ route('company.clients.show', [$installation->client, 'vista' => 'puertas']) }}" class="text-indigo-400 hover:text-indigo-300">ficha del cliente</a>.
        </p>
    </div>

    @if ($installation->hasCoordinates() && $maps['api_key'])
        @push('scripts')
            <script>
                window.initInstallationPinMap = function () {
                    const pos = {
                        lat: {{ (float) $installation->latitude }},
                        lng: {{ (float) $installation->longitude }},
                    };
                    const el = document.getElementById('installation-pin-map');
                    if (!el || !window.google?.maps) return;
                    const map = new google.maps.Map(el, {
                        center: pos,
                        zoom: {{ (int) $maps['zoom'] }},
                        mapTypeId: google.maps.MapTypeId.SATELLITE,
                        streetViewControl: false,
                    });
                    new google.maps.Marker({
                        position: pos,
                        map,
                        title: @json($installation->name),
                    });
                    google.maps.event.addListenerOnce(map, 'idle', function () {
                        google.maps.event.trigger(map, 'resize');
                        map.setCenter(pos);
                    });
                };
            </script>
            <script src="https://maps.googleapis.com/maps/api/js?key={{ $maps['api_key'] }}&callback=initInstallationPinMap" async defer></script>
        @endpush
    @endif
</x-company-layout>
