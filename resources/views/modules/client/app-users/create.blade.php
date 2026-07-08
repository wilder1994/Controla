<x-client-layout title="Nuevo usuario APP">
    <form action="{{ route('client.app-users.store') }}" method="POST" class="max-w-xl space-y-4 rounded-xl border border-slate-800 bg-slate-900 p-6">
        @csrf
        <div>
            <label class="block text-xs text-slate-400 mb-1">Usuario (sin sufijo)</label>
            <input type="text" name="username" required class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
            @if ($client)
                <p class="text-xs text-slate-500 mt-1">Login: usuario@{{ $client->login_suffix }}</p>
            @endif
        </div>
        <div>
            <label class="block text-xs text-slate-400 mb-1">Persona vinculada</label>
            <select name="member_id" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                <option value="">— Sin vínculo —</option>
                @foreach ($members as $member)
                    <option value="{{ $member->id }}">{{ $member->full_name }}</option>
                @endforeach
            </select>
        </div>
        <input type="email" name="email" placeholder="Email opcional" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
        <input type="password" name="password" required minlength="8" placeholder="Contraseña" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
        <button type="submit" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-500">Crear usuario</button>
    </form>
</x-client-layout>
