<x-admin-layout title="Usuarios">
    <x-slot:actions>
        @can('platform.users.manage')
            <x-ui.button variant="platform" :href="route('admin.users.create')" size="sm">Nuevo usuario</x-ui.button>
        @endcan
    </x-slot:actions>

    <div class="max-w-5xl space-y-4">
        <form method="GET" class="flex gap-2">
            <x-ui.input name="q" :value="$search" placeholder="Buscar por nombre o email" accent="platform" class="max-w-xs" />
            <x-ui.button type="submit" variant="secondary" size="sm">Buscar</x-ui.button>
        </form>

        <div class="rounded-lg border border-slate-800 overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-900/80 text-slate-400 text-xs uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">Nombre</th>
                        <th class="px-4 py-3 text-left">Email</th>
                        <th class="px-4 py-3 text-left">Rol</th>
                        <th class="px-4 py-3 text-left">Empresa</th>
                        <th class="px-4 py-3 text-right"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse ($users as $user)
                        <tr class="hover:bg-slate-900/40">
                            <td class="px-4 py-3 text-white">{{ $user->name }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ $user->email }}</td>
                            <td class="px-4 py-3 text-slate-400">{{ $user->roles->first()?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-400">{{ $user->securityCompany?->trade_name ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.users.edit', $user) }}" class="text-violet-400 hover:text-violet-300">Editar</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">Sin usuarios.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $users->links() }}
    </div>
</x-admin-layout>
