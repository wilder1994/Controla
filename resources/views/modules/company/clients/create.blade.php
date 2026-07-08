<x-company-layout title="Nuevo cliente">
    <div class="max-w-2xl">
        <a href="{{ route('company.clients.index') }}" class="text-sm text-slate-400 hover:text-white">&larr; Volver al listado</a>
        <h2 class="mt-4 text-2xl font-bold text-white">Alta de cliente</h2>
        <p class="text-sm text-slate-400 mt-1">Define el conjunto, sufijo de login y plan contratado.</p>

        <form method="POST" action="{{ route('company.clients.store') }}" class="mt-8 space-y-5 rounded-xl border border-slate-800 bg-slate-900 p-6">
            @csrf

            <div>
                <label class="block text-sm font-medium text-slate-300">Nombre del conjunto</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       class="mt-1 w-full rounded-lg border-slate-700 bg-slate-950 text-white focus:border-indigo-500 focus:ring-indigo-500">
                @error('name')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-300">Slug interno</label>
                    <input type="text" name="slug" value="{{ old('slug') }}" required placeholder="palmas-del-ingenio"
                           class="mt-1 w-full rounded-lg border-slate-700 bg-slate-950 text-white focus:border-indigo-500 focus:ring-indigo-500">
                    @error('slug')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-300">Sufijo login APP</label>
                    <div class="mt-1 flex rounded-lg border border-slate-700 bg-slate-950 overflow-hidden">
                        <span class="px-3 py-2 text-slate-500 text-sm border-r border-slate-700">usuario@</span>
                        <input type="text" name="login_suffix" value="{{ old('login_suffix') }}" required placeholder="palmasdelingenio"
                               class="flex-1 bg-transparent px-3 py-2 text-white focus:outline-none">
                    </div>
                    @error('login_suffix')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-300">Plan</label>
                    <select name="plan_tier" required class="mt-1 w-full rounded-lg border-slate-700 bg-slate-950 text-white">
                        @foreach ($planTiers as $value => $label)
                            <option value="{{ $value }}" @selected(old('plan_tier') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('plan_tier')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-300">URL acceso (opcional)</label>
                    <input type="url" name="access_url" value="{{ old('access_url') }}"
                           class="mt-1 w-full rounded-lg border-slate-700 bg-slate-950 text-white">
                </div>
            </div>

            <label class="inline-flex items-center gap-2 text-sm text-slate-300">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-600 bg-slate-950 text-indigo-600">
                Cliente activo
            </label>

            <div class="pt-2">
                <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">
                    Crear cliente
                </button>
            </div>
        </form>
    </div>
</x-company-layout>
