<x-company-layout title="Nuevo usuario">
    <div class="max-w-2xl">
        <a href="{{ route('company.users.index') }}" class="text-sm text-slate-400 hover:text-white">&larr; Usuarios</a>
        <form method="POST" action="{{ route('company.users.store') }}" class="mt-4 space-y-4 rounded-lg border border-slate-800 bg-slate-900/80 p-4">
            @csrf
            @include('modules.shared.managed-user-form', [
                'roleOptions' => $roleOptions,
                'clients' => $clients,
                'managedUser' => null,
            ])
            <x-ui.button type="submit">Crear usuario</x-ui.button>
        </form>
    </div>
</x-company-layout>
