<x-client-layout title="Confirmar persona">
    <div class="max-w-2xl space-y-6">
        <p class="text-xs uppercase tracking-wide text-teal-500">Paso 2 de 2</p>
        <h2 class="text-2xl font-bold text-white">Accesos y confirmación</h2>

        <div class="rounded-xl border border-slate-800 bg-slate-900 p-4 text-sm text-slate-300 space-y-1">
            <p><span class="text-slate-500">Nombre:</span> {{ $step1['first_name'] }} {{ $step1['last_name'] }}</p>
            <p><span class="text-slate-500">Documento:</span> {{ $step1['document_number'] }}</p>
            <p><span class="text-slate-500">Unidad:</span> {{ $structure?->full_path ?? '—' }}</p>
        </div>

        <form action="{{ route('client.members.store') }}" method="POST" class="space-y-4 rounded-xl border border-slate-800 bg-slate-900 p-6">
            @csrf
            <label class="flex items-center gap-2 text-sm text-slate-300">
                <input type="checkbox" name="has_app_access" value="1" class="rounded border-slate-600 bg-slate-950 text-teal-600">
                Acceso APP móvil
            </label>
            <label class="flex items-center gap-2 text-sm text-slate-300">
                <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-600 bg-slate-950 text-teal-600">
                Persona activa en el censo
            </label>

            <div>
                <label class="block text-xs text-slate-400 mb-2">Porterías autorizadas (opcional)</label>
                @forelse ($locations as $location)
                    <label class="flex items-center gap-2 text-sm text-slate-300 mb-2">
                        <input type="checkbox" name="assigned_location_ids[]" value="{{ $location->id }}" class="rounded border-slate-600 bg-slate-950 text-teal-600">
                        {{ $location->name }} ({{ $location->code }})
                    </label>
                @empty
                    <p class="text-sm text-slate-500">No hay porterías registradas para este conjunto.</p>
                @endforelse
            </div>

            <div class="flex gap-3 pt-2">
                <a href="{{ route('client.members.create') }}" class="rounded-lg border border-slate-700 px-4 py-2 text-sm text-slate-300 hover:bg-slate-800">Volver</a>
                <button type="submit" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-500">Registrar y generar código QR</button>
            </div>
        </form>
    </div>
</x-client-layout>
