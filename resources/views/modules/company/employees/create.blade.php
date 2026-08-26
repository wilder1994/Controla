<x-company-layout title="Nuevo empleado">
    <x-slot:actions>
        <x-ui.button variant="secondary" :href="route('company.employees.index')" size="sm">← Empleados</x-ui.button>
    </x-slot:actions>

    <div class="max-w-3xl space-y-4">
        <p class="text-sm text-slate-400">Ficha de colaborador. La asignación a instalación y puesto se habilita cuando exista el árbol del cliente.</p>

        <form method="POST" action="{{ route('company.employees.store') }}" class="space-y-4 rounded-lg border border-slate-800 bg-slate-900/80 p-4">
            @csrf
            @include('modules.company.employees.partials.form-fields')
            <div class="pt-2">
                <x-ui.button type="submit" size="md">Crear empleado</x-ui.button>
            </div>
        </form>
    </div>
</x-company-layout>
