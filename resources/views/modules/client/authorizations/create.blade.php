<x-client-layout title="Nueva autorización">
    <form action="{{ route('client.authorizations.store') }}" method="POST" class="max-w-2xl space-y-4 rounded-xl border border-slate-800 bg-slate-900 p-6">
        @csrf
        <div>
            <label class="block text-xs text-slate-400 mb-1">Visitante</label>
            <input type="text" name="visitor_name" required class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
        </div>
        <input type="text" name="visitor_document" placeholder="Documento visitante" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
        <div>
            <label class="block text-xs text-slate-400 mb-1">Unidad destino</label>
            <select name="structure_id" required class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                @foreach ($structures as $structure)
                    <option value="{{ $structure->id }}">{{ $structure->full_path }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-slate-400 mb-1">Anfitrión (opcional)</label>
            <select name="member_id" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                <option value="">— Sin anfitrión —</option>
                @foreach ($members as $member)
                    <option value="{{ $member->id }}">{{ $member->full_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid sm:grid-cols-2 gap-4">
            <select name="visitor_category" required class="rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                @foreach ($categories as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            <input type="date" name="valid_for_date" required class="rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
        </div>
        <textarea name="notes" rows="2" placeholder="Notas" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white"></textarea>
        <button type="submit" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-500">Crear autorización</button>
    </form>
</x-client-layout>
