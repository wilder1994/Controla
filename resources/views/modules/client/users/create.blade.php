<x-client-layout title="Nuevo usuario portal">
    <div class="max-w-2xl">
        <a href="{{ route('client.users.index') }}" class="text-sm text-slate-400 hover:text-white">&larr; Usuarios portal</a>
        <form method="POST" action="{{ route('client.users.store') }}" enctype="multipart/form-data" class="mt-4 space-y-4 rounded-lg border border-slate-800 bg-slate-900/80 p-4">
            @csrf
            @include('modules.shared.managed-user-form', [
                'roleOptions' => $roleOptions,
                'managedUser' => null,
                'accent' => 'client',
            ])
            <x-ui.button type="submit" class="!bg-teal-600 hover:!bg-teal-500">Crear usuario</x-ui.button>
        </form>
    </div>
</x-client-layout>
