<x-client-layout title="Nueva persona">
    <div class="max-w-2xl">
        <p class="text-xs uppercase tracking-wide text-teal-500 mb-2">Paso 1 de 2</p>
        <h2 class="text-2xl font-bold text-white mb-6">Datos básicos de la persona</h2>
        <form action="{{ route('client.members.create.step1') }}" method="POST" class="space-y-4 rounded-xl border border-slate-800 bg-slate-900 p-6">
            @csrf
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs text-slate-400 mb-1">Nombres</label>
                    <input type="text" name="first_name" value="{{ old('first_name') }}" required class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                </div>
                <div>
                    <label class="block text-xs text-slate-400 mb-1">Apellidos</label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}" required class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                </div>
            </div>
            <div>
                <label class="block text-xs text-slate-400 mb-1">Documento</label>
                <input type="text" name="document_number" value="{{ old('document_number') }}" required class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
            </div>
            <div>
                <label class="block text-xs text-slate-400 mb-1">Unidad</label>
                <select name="structure_id" required class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                    @foreach ($structures as $structure)
                        <option value="{{ $structure->id }}" @selected(old('structure_id') == $structure->id)>{{ $structure->full_path }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-400 mb-1">Tipo</label>
                <select name="member_type" required class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                    @foreach ($memberTypes as $value => $label)
                        <option value="{{ $value }}" @selected(old('member_type') == $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs text-slate-400 mb-1">Teléfono</label>
                    <input type="text" name="phone_primary" value="{{ old('phone_primary') }}" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                </div>
                <div>
                    <label class="block text-xs text-slate-400 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                </div>
            </div>
            <button type="submit" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-500">Continuar al paso 2</button>
        </form>
    </div>
</x-client-layout>
