<x-company-layout :title="$employee->fullName()">
    <x-slot:actions>
        @if ($employee->is_active)
            <x-ui.button variant="secondary" :href="route('company.employees.edit', $employee)" size="sm">Editar</x-ui.button>
        @endif
        <x-ui.button variant="secondary" :href="route('company.employees.index')" size="sm">← Empleados</x-ui.button>
    </x-slot:actions>

    <div class="max-w-3xl space-y-4">
        <div class="rounded-lg border border-slate-800 bg-slate-900/80 p-4 space-y-3">
            <div class="flex flex-wrap items-center gap-2 text-xs">
                @if ($employee->is_active)
                    <span class="px-2 py-0.5 rounded-full bg-emerald-900/40 text-emerald-300">Activo</span>
                @else
                    <span class="px-2 py-0.5 rounded-full bg-slate-800 text-slate-400">Archivado{{ $employee->ceased_at ? ' · '.$employee->ceased_at->format('d/m/Y') : '' }}</span>
                @endif
            </div>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                <div>
                    <dt class="text-xs text-slate-500">Documento</dt>
                    <dd class="text-white">{{ $employee->document_type }} {{ $employee->document_number }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">Cargo</dt>
                    <dd class="text-white">{{ $employee->jobTitle?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">Tipo</dt>
                    <dd class="text-white">{{ $employee->collaboratorType?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">Correo de ficha</dt>
                    <dd class="text-white">{{ $employee->email }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">Sexo / edad</dt>
                    <dd class="text-white">{{ $employee->sex?->label() }} · {{ $employee->age() ?? '—' }} años</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">Nacimiento</dt>
                    <dd class="text-white">{{ $employee->birth_date?->format('d/m/Y') }} · {{ $employee->nationality }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">Grupo sanguíneo</dt>
                    <dd class="text-white">{{ $employee->blood_group?->label() }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">Emergencia</dt>
                    <dd class="text-white">{{ $employee->emergency_contact ?: '—' }} {{ $employee->emergency_phone ? '· '.$employee->emergency_phone : '' }}</dd>
                </div>
            </dl>
        </div>

        @if ($employee->is_active)
            <form method="POST" action="{{ route('company.employees.archive', $employee) }}" onsubmit="return confirm('¿Archivar este empleado? Si tiene usuario, se desactivará el acceso.')">
                @csrf
                <button type="submit" class="text-xs text-rose-400 hover:text-rose-300">Archivar empleado</button>
            </form>
        @else
            <form method="POST" action="{{ route('company.employees.restore', $employee) }}">
                @csrf
                <x-ui.button type="submit" variant="secondary" size="sm">Restaurar empleado</x-ui.button>
            </form>
        @endif
    </div>
</x-company-layout>
