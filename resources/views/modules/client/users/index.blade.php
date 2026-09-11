<x-client-layout title="Usuarios">
    <x-slot:headerTabs>
        <a href="{{ route('client.users.index', array_filter(['q' => $search ?: null, 'status' => 'active'])) }}"
           @class(['admin-header-tab', 'is-active' => $status === 'active'])>Activos</a>
        <a href="{{ route('client.users.index', array_filter(['q' => $search ?: null, 'status' => 'inactive'])) }}"
           @class(['admin-header-tab', 'is-active' => $status === 'inactive'])>Desactivados</a>
    </x-slot:headerTabs>

    <div class="max-w-5xl space-y-4">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="w-full max-w-xs">
                <x-ui.input name="q" :value="$search" placeholder="Buscar" accent="client" />
            </div>
            <x-ui.button type="submit" variant="secondary" size="sm">Buscar</x-ui.button>
            @can('create', App\Models\User::class)
                <x-ui.button :href="route('client.users.create')" size="sm" class="sm:ml-auto !bg-teal-600 hover:!bg-teal-500">+ Nuevo administrador</x-ui.button>
            @else
                <p class="sm:ml-auto text-xs text-slate-500">Los administradores los crea la empresa o la plataforma.</p>
            @endcan
        </form>

        <div class="rounded-lg border border-slate-800 overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-900/80 text-slate-400 text-xs uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">Nombre</th>
                        <th class="px-4 py-3 text-left">Usuario</th>
                        <th class="px-4 py-3 text-left">Email</th>
                        <th class="px-4 py-3 text-left">Rol</th>
                        <th class="px-4 py-3 text-right"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse ($users as $user)
                        <tr class="hover:bg-slate-900/40">
                            <td class="px-4 py-3 text-white">{{ $user->name }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-300">{{ $user->username ?: '—' }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ $user->email ?: '—' }}</td>
                            <td class="px-4 py-3 text-slate-400">{{ \App\Support\Auth\AssignableRoles::label($user->roles->first()?->name ?? '') }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('client.users.edit', $user) }}" class="text-teal-400 hover:text-teal-300">Editar</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">{{ $status === 'inactive' ? 'Sin administradores desactivados.' : 'Sin administradores activos.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $users->links() }}
    </div>
</x-client-layout>
