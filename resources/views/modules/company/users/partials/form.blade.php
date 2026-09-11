@php
    use App\Support\Auth\AssignableRoles;
    $isEdit = $managedUser !== null;
    $employee = $managedUser?->employee;
    $selectedRole = old('role', $managedUser?->getRoleNames()->first() ?? 'supervisor');
    $selectedJobTitle = old('job_title', $managedUser?->job_title ?: $employee?->jobTitle?->name);
    $selectedClients = collect(old('client_ids', $managedUser?->clients->pluck('id')->all() ?? []))
        ->map(fn ($id) => (int) $id)
        ->all();
    $requiresClients = in_array($selectedRole, AssignableRoles::requiringClientAssignment(), true);
    $singleClient = in_array($selectedRole, AssignableRoles::requiringSingleClientAssignment(), true);
    $isSupervisor = $selectedRole === 'supervisor';
    $formConfig = [
        'isEdit' => $isEdit,
        'searchUrl' => $isEdit ? '' : route('company.users.employee-search'),
        'previewUrl' => $isEdit ? '' : route('company.users.credentials-preview'),
        'rolesNeedingClients' => AssignableRoles::requiringClientAssignment(),
        'singleClientRoles' => AssignableRoles::requiringSingleClientAssignment(),
        'employeeId' => (string) old('employee_id', $employee?->id ?? ''),
        'employeeName' => old('employee_name', $employee?->fullName() ?? ''),
        'documentNumber' => old('document_number', $employee?->document_number ?? ''),
        'personalEmail' => old('personal_email', $employee?->email ?? ''),
        'username' => old('username', $managedUser?->username ?? ''),
        'jobTitle' => (string) $selectedJobTitle,
        'role' => $selectedRole,
        'generated' => filled(old('username')),
    ];
@endphp

<script type="application/json" id="company-user-form-config">@json($formConfig)</script>
<script>
    window.companyUserForm = function () {
        const cfg = JSON.parse(document.getElementById('company-user-form-config').textContent)
        return {
            isEdit: cfg.isEdit,
            employeeId: cfg.employeeId,
            employeeName: cfg.employeeName,
            documentNumber: cfg.documentNumber,
            personalEmail: cfg.personalEmail,
            username: cfg.username,
            jobTitle: cfg.jobTitle,
            role: cfg.role,
            password: '',
            generated: cfg.generated,
            loading: false,
            nameResults: [],
            documentResults: [],
            get needsClients() { return cfg.rolesNeedingClients.includes(this.role) },
            get singleClient() { return cfg.singleClientRoles.includes(this.role) },
            get isSupervisor() { return this.role === 'supervisor' },
            async search(field) {
                if (this.isEdit) return
                const q = (field === 'document' ? this.documentNumber : this.employeeName).trim()
                if (q.length < 2) {
                    if (field === 'document') this.documentResults = []
                    else this.nameResults = []
                    return
                }
                const res = await fetch(cfg.searchUrl + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } })
                const data = await res.json()
                const rows = data.employees || []
                if (field === 'document') this.documentResults = rows
                else this.nameResults = rows
            },
            async pick(row) {
                this.employeeId = String(row.id)
                this.employeeName = row.name
                this.documentNumber = row.document_number
                this.personalEmail = row.email || ''
                this.jobTitle = row.job_title || this.jobTitle
                this.nameResults = []
                this.documentResults = []
                await this.generate()
            },
            async generate() {
                if (this.isEdit || ! this.employeeId) { alert('Seleccione el empleado.'); return }
                this.loading = true
                try {
                    const body = new FormData()
                    body.append('employee_id', this.employeeId)
                    body.append('_token', document.querySelector('meta[name=csrf-token]').content)
                    const res = await fetch(cfg.previewUrl, { method: 'POST', headers: { Accept: 'application/json' }, body })
                    const data = await res.json()
                    if (! res.ok) throw new Error(data.message || (data.errors && Object.values(data.errors)[0][0]) || 'No se pudo generar')
                    this.username = data.username
                    this.password = data.password
                    this.generated = true
                } catch (e) {
                    alert(e.message)
                } finally {
                    this.loading = false
                }
            },
        }
    }
</script>

<div class="space-y-4" x-data="companyUserForm()">
    <input type="hidden" name="employee_id" :value="employeeId">
    @if ($isEdit)
        <input type="hidden" name="name" value="{{ $employee?->fullName() ?: $managedUser->name }}">
    @endif

    <div>
        <x-ui.label>Foto de perfil</x-ui.label>
        <div class="mt-1 flex items-center gap-3">
            @if ($managedUser?->avatar_path)
                <img src="{{ asset('storage/'.$managedUser->avatar_path) }}" alt="" class="h-14 w-14 rounded-full object-cover border border-slate-700">
            @else
                <div class="h-14 w-14 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center text-xs text-slate-500">Sin foto</div>
            @endif
            <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" class="block w-full text-xs text-slate-400 file:mr-3 file:rounded file:border-0 file:bg-slate-800 file:px-3 file:py-1.5 file:text-xs file:text-slate-200">
        </div>
        <x-ui.field-error :messages="$errors->get('avatar')" />
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div class="relative">
            <x-ui.label for="employee-name">Nombre</x-ui.label>
            <input
                id="employee-name"
                type="text"
                x-model="employeeName"
                @input.debounce.250ms="search('name')"
                autocomplete="off"
                @readonly($isEdit)
                placeholder="Filtrar por nombre"
                class="w-full h-9 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white"
            >
            <div x-show="nameResults.length" class="absolute z-20 mt-1 w-full max-h-48 overflow-auto rounded-lg border border-slate-700 bg-slate-950">
                <template x-for="row in nameResults" :key="'n'+row.id">
                    <button type="button" class="block w-full text-left px-3 py-2 text-sm text-slate-200 hover:bg-slate-800" @click="pick(row)" x-text="row.name + ' · ' + row.document_number"></button>
                </template>
            </div>
            <x-ui.field-error :messages="$errors->get('employee_id')" />
        </div>
        <div class="relative">
            <x-ui.label for="employee-document">Cédula</x-ui.label>
            <input
                id="employee-document"
                type="text"
                x-model="documentNumber"
                @input.debounce.250ms="search('document')"
                autocomplete="off"
                @readonly($isEdit)
                placeholder="Filtrar por cédula"
                class="w-full h-9 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white"
            >
            <div x-show="documentResults.length" class="absolute z-20 mt-1 w-full max-h-48 overflow-auto rounded-lg border border-slate-700 bg-slate-950">
                <template x-for="row in documentResults" :key="'d'+row.id">
                    <button type="button" class="block w-full text-left px-3 py-2 text-sm text-slate-200 hover:bg-slate-800" @click="pick(row)" x-text="row.document_number + ' · ' + row.name"></button>
                </template>
            </div>
        </div>
    </div>

    <div>
        <x-ui.label for="username">Usuario de acceso</x-ui.label>
        <input type="text" id="username-display" :value="username" readonly class="w-full h-9 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white font-mono">
        @if (! $isEdit)
            <input type="hidden" name="username" :value="username">
        @endif
        <p class="mt-1 text-[11px] text-slate-500">Primer nombre + primer apellido + 4 dígitos. Es el login. No es la cédula.</p>
        <x-ui.field-error :messages="$errors->get('username')" />
    </div>

    <div>
        <x-ui.label for="job_title">Cargo / función</x-ui.label>
        <select name="job_title" id="job_title" x-model="jobTitle" required class="w-full h-9 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white">
            <option value="">Seleccione</option>
            @foreach ($jobTitles as $title)
                <option value="{{ $title->name }}" @selected($selectedJobTitle === $title->name)>{{ $title->name }}</option>
            @endforeach
            @if ($selectedJobTitle && $jobTitles->where('name', $selectedJobTitle)->isEmpty())
                <option value="{{ $selectedJobTitle }}" selected>{{ $selectedJobTitle }}</option>
            @endif
        </select>
        <p class="mt-1 text-[11px] text-slate-500">Catálogo de la empresa. Al elegir el empleado se precarga el cargo de la ficha.</p>
        <x-ui.field-error :messages="$errors->get('job_title')" />
    </div>

    <div>
        <x-ui.label for="personal-email">Email personal</x-ui.label>
        <input id="personal-email" type="email" :value="personalEmail" readonly class="w-full h-9 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white">
        <p class="mt-1 text-[11px] text-slate-500">Viene de la ficha del empleado. No es el login. Más adelante se usará para enviar usuario y clave.</p>
    </div>

    <div>
        <x-ui.label for="role">Rol</x-ui.label>
        <select name="role" id="role" x-model="role" required class="w-full h-9 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white">
            @foreach ($roleOptions as $role)
                <option value="{{ $role }}" @selected($role === $selectedRole)>{{ AssignableRoles::label($role) }}</option>
            @endforeach
        </select>
        <x-ui.field-error :messages="$errors->get('role')" />
    </div>

    @if ($isEdit && $managedUser?->supervisor_code)
        <div x-show="isSupervisor" class="rounded-lg border border-slate-800 bg-slate-950/50 p-3">
            <p class="text-xs text-slate-500">Código de revista (permanente)</p>
            <p class="mt-1 font-mono text-lg tracking-widest text-indigo-300">{{ $managedUser->supervisor_code }}</p>
            <label class="mt-2 inline-flex items-center gap-2 text-xs text-slate-400">
                <input type="checkbox" name="regenerate_supervisor_code" value="1" class="rounded border-slate-600 bg-slate-950 text-indigo-600">
                Regenerar código (rotación deliberada)
            </label>
        </div>
    @endif

    <div x-show="needsClients">
        <x-ui.label>Cliente</x-ui.label>
        <div class="mt-2 space-y-2 rounded-lg border border-slate-800 bg-slate-950/50 p-3 max-h-48 overflow-auto">
            @foreach ($clients as $client)
                <label class="flex items-center gap-2 text-sm text-slate-300">
                    <input
                        type="{{ $singleClient ? 'radio' : 'checkbox' }}"
                        name="client_ids[]"
                        value="{{ $client->id }}"
                        class="rounded border-slate-600 bg-slate-950 text-indigo-600"
                        :type="singleClient ? 'radio' : 'checkbox'"
                        x-bind:disabled="!needsClients"
                        @checked(in_array((int) $client->id, $selectedClients, true))
                    >
                    <span>{{ $client->name }}</span>
                </label>
            @endforeach
        </div>
        <p x-show="singleClient" class="mt-2 text-[11px] text-slate-500">El vigilante opera portería solo si está asignado a un puesto de una instalación con puertas. Sin puertas no se crea ese acceso.</p>
        <x-ui.field-error :messages="$errors->get('client_ids')" />
    </div>

    @if (! $isEdit)
        <div class="space-y-3 rounded-lg border border-slate-800 bg-slate-950/50 p-3">
            <p class="text-xs text-slate-500">Al elegir el empleado se genera el usuario (nombre.apellido + 4 dígitos) y una clave. Puede volver a generar.</p>
            <button type="button" @click="generate()" :disabled="loading || !employeeId" class="h-9 px-3 rounded-lg bg-slate-800 text-xs font-semibold text-white">
                <span x-text="loading ? 'Generando…' : 'Generar usuario y contraseña'"></span>
            </button>
            <div x-show="generated" class="text-sm space-y-1">
                <p class="text-slate-400">Usuario: <span class="font-mono text-white" x-text="username"></span></p>
                <p class="text-slate-400">Contraseña: <span class="font-mono text-white" x-text="password"></span></p>
            </div>
            <input type="hidden" name="password" :value="password">
            <input type="hidden" name="password_confirmation" :value="password">
            <x-ui.field-error :messages="$errors->get('password')" />
        </div>
    @else
        <div>
            <x-ui.label for="password">Nueva contraseña (opcional)</x-ui.label>
            <x-ui.input type="password" id="password" name="password" autocomplete="new-password" />
            <x-ui.field-error :messages="$errors->get('password')" />
        </div>
        <div>
            <x-ui.label for="password_confirmation">Confirmar contraseña</x-ui.label>
            <x-ui.input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" />
        </div>
    @endif

    <label class="inline-flex items-center gap-2 text-sm text-slate-300">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $managedUser?->is_active ?? true)) class="rounded border-slate-600 bg-slate-950 text-indigo-600">
        Usuario activo
    </label>
</div>
