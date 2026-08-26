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
                @if ($employee->user)
                    <span class="px-2 py-0.5 rounded-full bg-indigo-900/40 text-indigo-300">Con acceso</span>
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
                    <dt class="text-xs text-slate-500">Correo</dt>
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

        <div class="rounded-lg border border-slate-800 bg-slate-900/80 p-4">
            <p class="text-sm font-medium text-white">Asignación</p>
            <p class="mt-1 text-xs text-slate-500">Instalación y puesto quedan vacíos hasta que exista el árbol del cliente.</p>
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

        @if ($employee->user)
            <div class="rounded-lg border border-slate-800 bg-slate-900/80 p-4 space-y-2">
                <p class="text-sm font-medium text-white">Acceso a plataforma</p>
                <p class="text-sm text-slate-300">{{ $employee->user->email }} · {{ $employee->user->roles->first()?->name ? \App\Support\Auth\AssignableRoles::label($employee->user->roles->first()->name) : '—' }}</p>
                @if ($employee->user->supervisor_code)
                    <p class="text-xs text-slate-400">Código de revista: <span class="font-mono text-white">{{ $employee->user->supervisor_code }}</span></p>
                @endif
                @if ($employee->user->clients->isNotEmpty())
                    <p class="text-xs text-slate-400">Conjuntos: {{ $employee->user->clients->pluck('name')->join(', ') }}</p>
                @endif
                <a href="{{ route('company.users.edit', $employee->user) }}" class="text-xs text-indigo-400 hover:text-indigo-300">Editar usuario</a>
            </div>
        @elseif ($canGrantAccess && $employee->is_active)
            <div
                class="rounded-lg border border-slate-800 bg-slate-900/80 p-4"
                x-data="{ role: '{{ old('role', 'supervisor') }}' }"
            >
                <p class="text-sm font-medium text-white">Dar acceso</p>
                <p class="mt-1 text-xs text-slate-500">Crea un usuario con el correo y nombre de esta ficha. Rol de conjunto no se asigna desde aquí.</p>

                <form method="POST" action="{{ route('company.employees.access', $employee) }}" class="mt-4 space-y-3">
                    @csrf
                    <div>
                        <x-ui.label for="role">Rol</x-ui.label>
                        <select name="role" id="role" x-model="role" required class="w-full h-9 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white">
                            @foreach ($roleOptions as $role)
                                <option value="{{ $role }}">{{ \App\Support\Auth\AssignableRoles::label($role) }}</option>
                            @endforeach
                        </select>
                        <x-ui.field-error :messages="$errors->get('role')" />
                    </div>

                    <div x-show="role === 'guardia'" x-cloak>
                        <x-ui.label for="client_ids">Conjunto</x-ui.label>
                        <select name="client_ids[]" id="client_ids" class="w-full h-9 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white">
                            <option value="">Seleccione…</option>
                            @foreach ($clients as $client)
                                <option value="{{ $client->id }}" @selected((string) old('client_ids.0') === (string) $client->id)>{{ $client->name }}</option>
                            @endforeach
                        </select>
                        <x-ui.field-error :messages="$errors->get('client_ids')" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <x-ui.label for="password">Contraseña</x-ui.label>
                            <x-ui.input id="password" type="password" name="password" required autocomplete="new-password" />
                            <x-ui.field-error :messages="$errors->get('password')" />
                        </div>
                        <div>
                            <x-ui.label for="password_confirmation">Confirmar contraseña</x-ui.label>
                            <x-ui.input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
                        </div>
                    </div>

                    <x-ui.button type="submit" size="sm">Crear acceso</x-ui.button>
                </form>
            </div>
        @endif
    </div>
</x-company-layout>
