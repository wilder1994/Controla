<x-client-layout :title="$installation->name" :wide="true">
    <div
        class="space-y-4"
        x-data="{ parentId: @js((string) old('parent_id', '')) }"
        @structure-parent.window="parentId = String($event.detail.id); $nextTick(() => { $refs.name?.focus(); $refs.createForm?.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); })"
    >
        <a href="{{ route('client.installations.index') }}" class="text-sm text-slate-400 hover:text-white">← Instalaciones</a>

        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-stretch">
            <div class="min-w-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-900">
                @if ($installation->hasCoordinates() && $maps['api_key'])
                    <div id="client-installation-pin-map" class="h-96 w-full"></div>
                @elseif ($installation->hasCoordinates())
                    <p class="p-4 text-xs text-slate-500">Pin {{ $installation->latitude }}, {{ $installation->longitude }}.</p>
                @else
                    <p class="p-4 text-sm text-slate-500">Esta instalación no tiene pin en el mapa.</p>
                @endif
            </div>
            <div class="rounded-lg border border-slate-800 bg-slate-900/80 p-4 space-y-3">
                <div>
                    <p class="text-xs text-slate-500">Instalación</p>
                    <h3 class="text-lg font-semibold text-white">{{ $installation->name }}</h3>
                </div>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs text-slate-500">Código</dt>
                        <dd class="font-mono text-teal-300">{{ $installation->code ?: '—' }}</dd>
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

        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start">
            <div class="min-w-0 rounded-xl border border-slate-800 bg-slate-900 p-4">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500 mb-4">Instalaciones</h3>
                @if ($tree->isEmpty())
                    <p class="text-slate-500 text-sm">No hay nodos en esta instalación. Crea el primero (Torre A, Salón A, etc.).</p>
                @else
                    <x-client.structure-tree :nodes="$tree" :census="$census" :pick-parent="true" />
                @endif
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-900 p-4 lg:sticky lg:top-20" x-ref="createForm">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500 mb-4">Nuevo nodo</h3>
                <form action="{{ route('client.structures.store') }}" method="POST" class="space-y-3">
                    @csrf
                    <input type="hidden" name="installation_id" value="{{ $installation->id }}">
                    <div>
                        <label class="block text-xs text-slate-400 mb-1" for="structure_name">Nombre</label>
                        <input id="structure_name" type="text" name="name" x-ref="name" value="{{ old('name') }}" required placeholder="Torre A, Salón B…" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                        @if ($client->structureType)
                            <p class="mt-1 text-[11px] text-slate-500">Tipo: {{ $client->structureType->name }}</p>
                        @else
                            <p class="mt-1 text-[11px] text-amber-400">Sin tipo en la ficha del cliente.</p>
                        @endif
                        @error('name')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1" for="parent_id">Crear dentro de</label>
                        <select id="parent_id" name="parent_id" x-model="parentId" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                            <option value="">{{ $installation->name }} (esta instalación)</option>
                            @foreach ($parentOptions as $parent)
                                <option value="{{ $parent['id'] }}">{{ str_repeat("\u{00A0}\u{00A0}", $parent['depth'] + 1).$parent['name'] }}</option>
                            @endforeach
                        </select>
                        @error('parent_id')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-500">
                        Crear estructura
                    </button>
                </form>
            </div>
        </div>
    </div>

    @if ($installation->hasCoordinates() && $maps['api_key'])
        @push('scripts')
            <script>
                window.initClientInstallationPinMap = function () {
                    const pos = {
                        lat: {{ (float) $installation->latitude }},
                        lng: {{ (float) $installation->longitude }},
                    };
                    const el = document.getElementById('client-installation-pin-map');
                    if (!el || !window.google?.maps) return;
                    const map = new google.maps.Map(el, {
                        center: pos,
                        zoom: {{ (int) $maps['zoom'] }},
                        mapTypeId: google.maps.MapTypeId.SATELLITE,
                        streetViewControl: false,
                    });
                    new google.maps.Marker({ position: pos, map, title: @json($installation->name) });
                    google.maps.event.addListenerOnce(map, 'idle', function () {
                        google.maps.event.trigger(map, 'resize');
                        map.setCenter(pos);
                    });
                };
            </script>
            <script src="https://maps.googleapis.com/maps/api/js?key={{ $maps['api_key'] }}&callback=initClientInstallationPinMap" async defer></script>
        @endpush
    @endif
</x-client-layout>
