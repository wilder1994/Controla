<x-company-layout :title="'Editar: '.$client->name">
    <div class="max-w-2xl">
        <a href="{{ route('company.clients.show', $client) }}" class="text-sm text-slate-400 hover:text-white">&larr; Detalle cliente</a>

        <form method="POST" action="{{ route('company.clients.update', $client) }}" class="mt-6 space-y-5 rounded-xl border border-slate-800 bg-slate-900 p-6">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-slate-300">Nombre</label>
                <input type="text" name="name" value="{{ old('name', $client->name) }}" required
                       class="mt-1 w-full rounded-lg border-slate-700 bg-slate-950 text-white">
                @error('name')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-300">Slug</label>
                    <input type="text" name="slug" value="{{ old('slug', $client->slug) }}" required
                           class="mt-1 w-full rounded-lg border-slate-700 bg-slate-950 text-white">
                    @error('slug')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-300">Sufijo login</label>
                    <input type="text" name="login_suffix" value="{{ old('login_suffix', $client->login_suffix) }}" required
                           class="mt-1 w-full rounded-lg border-slate-700 bg-slate-950 text-white">
                    @error('login_suffix')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-300">Plan</label>
                    <select name="plan_tier" class="mt-1 w-full rounded-lg border-slate-700 bg-slate-950 text-white">
                        @foreach ($planTiers as $value => $label)
                            <option value="{{ $value }}" @selected(old('plan_tier', $client->plan_tier->value) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-300">URL acceso</label>
                    <input type="url" name="access_url" value="{{ old('access_url', $client->access_url) }}"
                           class="mt-1 w-full rounded-lg border-slate-700 bg-slate-950 text-white">
                </div>
            </div>

            <label class="inline-flex items-center gap-2 text-sm text-slate-300">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $client->is_active)) class="rounded border-slate-600 bg-slate-950 text-indigo-600">
                Cliente activo
            </label>

            <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">
                Guardar cambios
            </button>
        </form>
    </div>
</x-company-layout>
