<x-client-layout :title="$member->full_name">
    <div class="space-y-6 max-w-3xl" x-data="{ tab: 'datos' }">
        <div>
            <a href="{{ route('client.members.index') }}" class="text-sm text-teal-400 hover:text-teal-300">← Directorio</a>
            <h2 class="text-2xl font-bold text-white mt-2">{{ $member->full_name }}</h2>
            <p class="text-sm text-slate-400">{{ $member->structure?->full_path }} · {{ $member->member_type->label() }}</p>
        </div>

        <div class="border-b border-slate-800">
            <nav class="flex gap-4 text-sm">
                <button type="button" @click="tab = 'datos'" :class="tab === 'datos' ? 'border-teal-500 text-teal-300' : 'border-transparent text-slate-500'" class="px-1 py-2 border-b-2 font-medium">Datos</button>
                <button type="button" @click="tab = 'accesos'" :class="tab === 'accesos' ? 'border-teal-500 text-teal-300' : 'border-transparent text-slate-500'" class="px-1 py-2 border-b-2 font-medium">Accesos portería</button>
            </nav>
        </div>

        <div x-show="tab === 'datos'" x-cloak class="grid md:grid-cols-2 gap-6">
            <div class="rounded-xl border border-slate-800 bg-slate-900 p-4 space-y-3">
                <h3 class="text-sm font-semibold text-white">Datos de contacto</h3>
                <p class="text-sm text-slate-300">Documento: <span class="font-mono">{{ $member->document_number }}</span></p>
                <p class="text-sm text-slate-300">Teléfono: {{ $member->phone_primary ?? '—' }}</p>
                <p class="text-sm text-slate-300">Email: {{ $member->email ?? '—' }}</p>
                <p class="text-sm text-slate-300">APP móvil: {{ $member->has_app_access ? 'Sí' : 'No' }}</p>
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
                <h3 class="text-sm font-semibold text-white mb-3">Código de acceso / QR</h3>
                <p class="font-mono text-lg text-indigo-300 mb-4">{{ $member->access_code }}</p>
                <div id="member-qr" class="bg-white p-3 inline-block rounded-lg" data-code="{{ $member->access_code }}"></div>
                <p class="text-xs text-slate-500 mt-3">Escaneable en portería para validar identidad (integración operativa en Fase 2).</p>
            </div>
        </div>

        <div x-show="tab === 'accesos'" x-cloak>
            <form action="{{ route('client.members.update', $member) }}" method="POST" class="rounded-xl border border-slate-800 bg-slate-900 p-6 space-y-4">
                @csrf
                @method('PATCH')
                <h3 class="text-sm font-semibold text-white">Porterías donde puede ingresar</h3>
                @forelse ($locations as $location)
                    <label class="flex items-center gap-2 text-sm text-slate-300">
                        <input type="checkbox" name="assigned_location_ids[]" value="{{ $location->id }}"
                            @checked(in_array($location->id, $assignedLocationIds, true))
                            class="rounded border-slate-600 bg-slate-950 text-teal-600">
                        {{ $location->name }} ({{ $location->code }})
                    </label>
                @empty
                    <p class="text-sm text-slate-500">No hay porterías activas en este conjunto.</p>
                @endforelse
                <button type="submit" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-500">Guardar accesos</button>
            </form>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <script>
        const el = document.getElementById('member-qr');
        if (el && window.QRCode) {
            QRCode.toCanvas(document.createElement('canvas'), el.dataset.code, { width: 160 }, (err, canvas) => {
                if (!err) el.appendChild(canvas);
            });
        }
    </script>
    @endpush
</x-client-layout>
