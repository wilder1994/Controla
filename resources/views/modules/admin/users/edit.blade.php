<x-admin-layout :title="'Editar: '.$managedUser->name">
    <div class="max-w-2xl">
        <a href="{{ route('admin.users.index') }}" class="text-sm text-slate-400 hover:text-white">&larr; Usuarios</a>
        <form method="POST" action="{{ route('admin.users.update', $managedUser) }}" enctype="multipart/form-data" class="mt-4 space-y-4 rounded-lg border border-slate-800 bg-slate-900/80 p-4">
            @csrf
            @method('PUT')
            @include('modules.shared.managed-user-form', [
                'roleOptions' => $roleOptions,
                'clients' => $clients,
                'companies' => $companies,
                'managedUser' => $managedUser,
                'accent' => 'platform',
                'showCompanySelect' => true,
            ])
            <x-ui.button type="submit" variant="platform">Guardar cambios</x-ui.button>
        </form>
    </div>
</x-admin-layout>
