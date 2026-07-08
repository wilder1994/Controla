<x-client-layout title="Usuarios APP">
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-white">Usuarios APP móvil</h2>
                <p class="text-sm text-slate-400">Login: <span class="font-mono text-indigo-300">usuario@{{ $client?->login_suffix }}</span></p>
            </div>
            <a href="{{ route('client.app-users.create') }}" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-500">Nuevo usuario</a>
        </div>
        <div class="rounded-xl border border-slate-800 overflow-hidden bg-slate-900">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-950/60">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs uppercase text-slate-500">Usuario</th>
                        <th class="px-4 py-3 text-left text-xs uppercase text-slate-500">Login APP</th>
                        <th class="px-4 py-3 text-left text-xs uppercase text-slate-500">Persona vinculada</th>
                        <th class="px-4 py-3 text-left text-xs uppercase text-slate-500">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse ($appUsers as $appUser)
                        <tr>
                            <td class="px-4 py-3 text-white">{{ $appUser->username }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-indigo-300">{{ $appUser->login_email }}</td>
                            <td class="px-4 py-3 text-slate-400">{{ $appUser->member?->full_name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                @if ($appUser->is_active)
                                    <span class="text-xs text-emerald-300">Activo</span>
                                @else
                                    <span class="text-xs text-slate-500">Inactivo</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">Sin usuarios APP.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $appUsers->links() }}
    </div>
</x-client-layout>
