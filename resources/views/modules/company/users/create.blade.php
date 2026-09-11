<x-company-layout title="Nuevo usuario">
    <div class="max-w-3xl">
        <a href="{{ route('company.users.index') }}" class="text-sm text-slate-400 hover:text-white">&larr; Usuarios</a>
        <form
            method="POST"
            action="{{ route('company.users.store') }}"
            enctype="multipart/form-data"
            class="mt-4 space-y-4 rounded-lg border border-slate-800 bg-slate-900/80 p-4"
        >
            @csrf
            @include('modules.company.users.partials.form', [
                'managedUser' => null,
                'roleOptions' => $roleOptions,
                'clients' => $clients,
                'jobTitles' => $jobTitles,
            ])
            <x-ui.button type="submit" size="sm">Crear usuario</x-ui.button>
        </form>
    </div>
</x-company-layout>
