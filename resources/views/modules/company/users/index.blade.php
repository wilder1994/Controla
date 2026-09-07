<x-company-layout title="Usuarios">
    <div class="max-w-5xl space-y-4">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-semibold text-white">Usuarios de la empresa</h3>
                <p class="text-xs text-slate-500 mt-1">Cuentas de acceso. Desactivar conserva el historial; la ficha vive en Empleados.</p>
            </div>
            <x-ui.button :href="route('company.users.create')" size="sm">+ Nuevo usuario</x-ui.button>
        </div>

        <div class="flex flex-wrap gap-1 border-b border-slate-800">
            <a href="{{ route('company.users.index', array_filter(['q' => $search ?: null, 'status' => 'active'])) }}"
               @class(['admin-header-tab', 'is-active' => $status === 'active'])>Activos</a>
            <a href="{{ route('company.users.index', array_filter(['q' => $search ?: null, 'status' => 'inactive'])) }}"
               @class(['admin-header-tab', 'is-active' => $status === 'inactive'])>Desactivados</a>
        </div>

        <form method="GET" class="flex gap-2">
            <input type="hidden" name="status" value="{{ $status }}">
            <x-ui.input name="q" :value="$search" placeholder="Buscar" class="max-w-xs" />
            <x-ui.button type="submit" variant="secondary" size="sm">Buscar</x-ui.button>
        </form>

        <div class="rounded-lg border border-slate-800 overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-900/80 text-slate-400 text-xs uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">Nombre</th>
                        <th class="px-4 py-3 text-left">Usuario</th>
                        <th class="px-4 py-3 text-left">Email</th>
                        <th class="px-4 py-3 text-left">Rol</th>
                        <th class="px-4 py-3 text-left">Conjuntos</th>
                        <th class="px-4 py-3 text-right"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse ($users as $user)
                        <tr class="hover:bg-slate-900/40">
                            <td class="px-4 py-3 text-white">{{ $user->name }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-300">{{ $user->username }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ $user->email ?: '—' }}</td>
                            <td class="px-4 py-3 text-slate-400">{{ $user->roles->first()?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-400 text-xs">{{ $user->clients->pluck('name')->join(', ') ?: '—' }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('company.users.edit', $user) }}" class="text-indigo-400 hover:text-indigo-300">Editar</a>
                                @if ($user->is_active && ! auth()->user()->is($user))
                                    <form method="POST" action="{{ route('company.users.deactivate', $user) }}" class="inline ml-3" onsubmit="return confirm('¿Desactivar este acceso? El historial no se borra.')">
                                        @csrf
                                        <button type="submit" class="text-amber-400 hover:text-amber-300">Desactivar</button>
                                    </form>
                                @elseif (! $user->is_active)
                                    <form method="POST" action="{{ route('company.users.reactivate', $user) }}" class="inline ml-3" onsubmit="return confirm('¿Reactivar este acceso?')">
                                        @csrf
                                        <button type="submit" class="text-emerald-400 hover:text-emerald-300">Reactivar</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">{{ $status === 'inactive' ? 'Sin usuarios desactivados.' : 'Sin usuarios activos.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $users->links() }}
    </div>
</x-company-layout>
