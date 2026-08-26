<x-company-layout :title="'Editar: '.$employee->fullName()">
    <x-slot:actions>
        <x-ui.button variant="secondary" :href="route('company.employees.show', $employee)" size="sm">← Ficha</x-ui.button>
    </x-slot:actions>

    <div class="max-w-3xl space-y-4">
        <form method="POST" action="{{ route('company.employees.update', $employee) }}" class="space-y-4 rounded-lg border border-slate-800 bg-slate-900/80 p-4">
            @csrf
            @method('PUT')
            @include('modules.company.employees.partials.form-fields', ['employee' => $employee])
            <div class="pt-2">
                <x-ui.button type="submit" size="md">Guardar cambios</x-ui.button>
            </div>
        </form>
    </div>
</x-company-layout>
