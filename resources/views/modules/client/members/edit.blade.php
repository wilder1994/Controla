<x-client-layout title="Editar persona">
    <div class="max-w-2xl">
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('client.members.show', $member) }}" class="text-sm text-teal-400 hover:text-teal-300">← Volver</a>
            <h2 class="text-2xl font-bold text-white">Editar: {{ $member->full_name }}</h2>
        </div>
        <form action="{{ route('client.members.update', $member) }}" method="POST" enctype="multipart/form-data" class="space-y-4 rounded-xl border border-slate-800 bg-slate-900 p-6">
            @csrf @method('PUT')
            <x-client.member-photo-picker :preview-url="$member->photo_path ? Storage::url($member->photo_path) : null" />
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs text-slate-400 mb-1">Nombres</label>
                    <input type="text" name="first_name" value="{{ old('first_name', $member->first_name) }}" required class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                </div>
                <div>
                    <label class="block text-xs text-slate-400 mb-1">Apellidos</label>
                    <input type="text" name="last_name" value="{{ old('last_name', $member->last_name) }}" required class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                </div>
            </div>
            <div>
                <label class="block text-xs text-slate-400 mb-1">Documento</label>
                <input type="text" name="document_number" value="{{ old('document_number', $member->document_number) }}" required class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
            </div>
            <x-client.census-node-picker
                :installations="$installations"
                :node-options="$nodeOptions"
                :installation-id="$installationId"
                :structure-id="$structureId"
            />
            <div>
                <label class="block text-xs text-slate-400 mb-1">Tipo</label>
                <select name="member_type_id" required class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                    @foreach ($memberTypes as $type)
                        <option value="{{ $type->id }}" @selected((string) old('member_type_id', $member->member_type_id) === (string) $type->id)>{{ $type->name }}</option>
                    @endforeach
                </select>
                @error('member_type_id')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs text-slate-400 mb-1">Teléfono</label>
                    <input type="text" name="phone_primary" value="{{ old('phone_primary', $member->phone_primary) }}" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                </div>
                <div>
                    <label class="block text-xs text-slate-400 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email', $member->email) }}" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-300">
                <input type="checkbox" name="has_app_access" value="1" class="rounded border-slate-600 bg-slate-950 text-teal-600" @checked(old('has_app_access', $member->has_app_access))>
                Acceso de persona (app / panel)
            </label>
            <div class="flex gap-3">
                <button type="submit" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-500">Actualizar persona</button>
                <a href="{{ route('client.members.show', $member) }}" class="rounded-lg bg-slate-700 px-4 py-2 text-sm text-white hover:bg-slate-600">Cancelar</a>
            </div>
        </form>
    </div>
</x-client-layout>
