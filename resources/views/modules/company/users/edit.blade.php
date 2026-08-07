<x-company-layout :title="'Editar: '.$managedUser->name">
    <div class="max-w-2xl">
        <a href="{{ route('company.users.index') }}" class="text-sm text-slate-400 hover:text-white">&larr; Usuarios</a>
        <form method="POST" action="{{ route('company.users.update', $managedUser) }}" enctype="multipart/form-data" class="mt-4 space-y-4 rounded-lg border border-slate-800 bg-slate-900/80 p-4">
            @csrf
            @method('PUT')
            @include('modules.shared.managed-user-form', [
                'roleOptions' => $roleOptions,
                'clients' => $clients,
                'managedUser' => $managedUser,
            ])
            <x-ui.button type="submit">Guardar cambios</x-ui.button>
        </form>
    </div>
</x-company-layout>
