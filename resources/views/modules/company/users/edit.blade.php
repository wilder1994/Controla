<x-company-layout :title="'Editar: '.($managedUser->employee?->fullName() ?: $managedUser->name)">
    <div class="max-w-2xl">
        <a href="{{ route('company.users.index') }}" class="text-sm text-slate-400 hover:text-white">&larr; Usuarios</a>

        @if (session('issued_login'))
            <div class="mt-4 rounded-lg border border-amber-500/40 bg-amber-500/10 p-4 space-y-1">
                <p class="text-sm font-medium text-amber-200">Credenciales (cópielas ahora; la clave no se vuelve a mostrar)</p>
                <p class="text-sm text-white font-mono">Usuario: {{ session('issued_login') }}</p>
                <p class="text-sm text-white font-mono">Contraseña: {{ session('issued_password') }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('company.users.update', $managedUser) }}" enctype="multipart/form-data" class="mt-4 space-y-4 rounded-lg border border-slate-800 bg-slate-900/80 p-4">
            @csrf
            @method('PUT')
            @include('modules.company.users.partials.form', [
                'managedUser' => $managedUser,
                'roleOptions' => $roleOptions,
                'clients' => $clients,
                'jobTitles' => $jobTitles,
            ])
            <x-ui.button type="submit">Guardar cambios</x-ui.button>
        </form>
    </div>
</x-company-layout>
