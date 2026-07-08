<x-client-layout :title="$member->full_name">
    <div class="space-y-6 max-w-3xl">
        <div>
            <a href="{{ route('client.members.index') }}" class="text-sm text-teal-400 hover:text-teal-300">← Directorio</a>
            <h2 class="text-2xl font-bold text-white mt-2">{{ $member->full_name }}</h2>
            <p class="text-sm text-slate-400">{{ $member->structure?->full_path }} · {{ $member->member_type->label() }}</p>
        </div>

        <div class="grid md:grid-cols-2 gap-6">
            <div class="rounded-xl border border-slate-800 bg-slate-900 p-4 space-y-3">
                <h3 class="text-sm font-semibold text-white">Datos de contacto</h3>
                <p class="text-sm text-slate-300">Documento: <span class="font-mono">{{ $member->document_number }}</span></p>
                <p class="text-sm text-slate-300">Teléfono: {{ $member->phone_primary ?? '—' }}</p>
                <p class="text-sm text-slate-300">Email: {{ $member->email ?? '—' }}</p>
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
                <h3 class="text-sm font-semibold text-white mb-3">Código de acceso / QR</h3>
                <p class="font-mono text-lg text-indigo-300 mb-4">{{ $member->access_code }}</p>
                <div id="member-qr" class="bg-white p-3 inline-block rounded-lg" data-code="{{ $member->access_code }}"></div>
                <p class="text-xs text-slate-500 mt-3">Escaneable en portería para validar identidad.</p>
            </div>
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
