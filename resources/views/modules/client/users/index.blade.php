<x-client-layout title="Usuarios portal">
    <div class="max-w-5xl space-y-4">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-semibold text-white">Usuarios del conjunto</h3>
                <p class="text-xs text-slate-500 mt-1">Residentes y vigilantes asignados a este conjunto.</p>
            </div>
            <x-ui.button :href="route('client.users.create')" size="sm">+ Nuevo usuario</x-ui.button>
        </div>

        <form method="GET" class="flex gap-2">
            <x-ui.input name="q" :value="$search" placeholder="Buscar" accent="client" class="max-w-xs" />
            <x-ui.button type="submit" variant="secondary" size="sm">Buscar</x-ui.button>
        </form>

        <div class="rounded-lg border border-slate-800 overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-900/80 text-slate-400 text-xs uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">Nombre</th>
                        <th class="px-4 py-3 text-left">Email</th>
                        <th class="px-4 py-3 text-left">Rol</th>
                        <th class="px-4 py-3 text-right"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse ($users as $user)
                        <tr class="hover:bg-slate-900/40">
                            <td class="px-4 py-3 text-white">{{ $user->name }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ $user->email }}</td>
                            <td class="px-4 py-3 text-slate-400">{{ $user->roles->first()?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('client.users.edit', $user) }}" class="text-teal-400 hover:text-teal-300">Editar</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">Sin usuarios.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $users->links() }}

        <p class="text-xs text-slate-500">
            Usuarios APP móvil del censo:
            <a href="{{ route('client.app-users.index') }}" class="text-teal-400 hover:text-teal-300">Gestionar usuarios APP</a>
        </p>
    </div>
</x-client-layout>
