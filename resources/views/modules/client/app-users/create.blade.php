<x-client-layout title="Nuevo acceso de persona">
    <form action="{{ route('client.app-users.store') }}" method="POST" class="max-w-xl space-y-4 rounded-xl border border-slate-800 bg-slate-900 p-6">
        @csrf
        <p class="text-sm text-slate-400">Cada persona del censo puede tener un acceso para entrar a la app o a un panel (reportes, pre-autorizaciones, etc.).</p>
        <div>
            <label class="block text-xs text-slate-400 mb-1">Persona de la estructura</label>
            <select name="member_id" required class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                <option value="">Seleccione la persona</option>
                @foreach ($members as $member)
                    <option value="{{ $member->id }}" @selected((string) old('member_id', $selectedMemberId) === (string) $member->id)>
                        {{ $member->full_name }} · {{ $member->structure?->full_path }}
                    </option>
                @endforeach
            </select>
            @error('member_id')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-xs text-slate-400 mb-1">Usuario (sin sufijo)</label>
            <input type="text" name="username" value="{{ old('username') }}" required class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
            @if ($client)
                <p class="text-xs text-slate-500 mt-1">Login: usuario@{{ $client->login_suffix }}</p>
            @endif
            @error('username')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
        </div>
        <input type="email" name="email" value="{{ old('email') }}" placeholder="Email opcional" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
        <input type="password" name="password" required minlength="8" placeholder="Contraseña" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
        <button type="submit" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-500">Crear acceso</button>
    </form>
</x-client-layout>
