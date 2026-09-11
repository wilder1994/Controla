@php
    use App\Support\Geo\ColombiaDivipola;

    $employee = $employee ?? null;
    $selectClass = 'w-full h-9 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/30';
    $colombiaPlaces = $colombiaPlaces ?? ColombiaDivipola::tree();
    $catalogStarterUrl = $catalogStarterUrl ?? route('company.employees.catalog-starter');
    $fichaConfig = [
        'places' => $colombiaPlaces,
        'birthDepartment' => old('birth_department', $employee?->birth_department ?? ''),
        'birthCity' => old('birth_city', $employee?->birth_city ?? ''),
        'issueDepartment' => old('document_issue_department', $employee?->document_issue_department ?? ''),
        'issueCity' => old('document_issue_city', $employee?->document_issue_city ?? ''),
        'types' => $collaboratorTypes->map(fn ($type) => ['id' => $type->id, 'name' => $type->name])->values()->all(),
        'titles' => $jobTitles->map(fn ($title) => ['id' => $title->id, 'name' => $title->name])->values()->all(),
        'selectedType' => (string) old('collaborator_type_id', $employee?->collaborator_type_id ?? ''),
        'selectedTitle' => (string) old('job_title_id', $employee?->job_title_id ?? ''),
        'catalogUrl' => $catalogStarterUrl,
        'photoPreview' => $employee?->photoUrl(),
    ];
@endphp

<div class="space-y-6" x-data="employeeFichaForm(@js($fichaConfig))">
    <div class="flex flex-wrap items-center gap-4">
        @include('modules.company.employees.partials.avatar', ['employee' => $employee, 'canEdit' => true])
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Foto</p>
            <p class="text-xs text-slate-400">JPG, PNG o WebP, máximo 2 MB. Clic en el círculo.</p>
            <x-ui.field-error :messages="$errors->get('photo')" />
        </div>
    </div>

    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Identidad</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-ui.label for="document_type">Tipo de documento</x-ui.label>
            <select name="document_type" id="document_type" required class="{{ $selectClass }}">
                <option value="">Seleccione…</option>
                @foreach ($documentTypes as $code => $label)
                    <option value="{{ $code }}" @selected(old('document_type', $employee?->document_type) === $code)>{{ $label }}</option>
                @endforeach
            </select>
            <x-ui.field-error :messages="$errors->get('document_type')" />
        </div>
        <div>
            <x-ui.label for="document_number">Número de documento</x-ui.label>
            <x-ui.input id="document_number" type="text" name="document_number" :value="old('document_number', $employee?->document_number)" required />
            <x-ui.field-error :messages="$errors->get('document_number')" />
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <x-ui.label for="document_issue_department">Departamento de expedición</x-ui.label>
            <select name="document_issue_department" id="document_issue_department" x-model="issueDepartment" @change="syncIssueCity()" class="{{ $selectClass }}">
                <option value="">Seleccione…</option>
                <template x-for="dept in departments" :key="'issue-'+dept">
                    <option :value="dept" x-text="dept"></option>
                </template>
            </select>
            <x-ui.field-error :messages="$errors->get('document_issue_department')" />
        </div>
        <div>
            <x-ui.label for="document_issue_city">Municipio de expedición</x-ui.label>
            <select name="document_issue_city" id="document_issue_city" x-model="issueCity" :disabled="!issueDepartment" class="{{ $selectClass }}">
                <option value="">Seleccione…</option>
                <template x-for="city in issueCities" :key="'issue-city-'+city">
                    <option :value="city" x-text="city"></option>
                </template>
            </select>
            <x-ui.field-error :messages="$errors->get('document_issue_city')" />
        </div>
        <div>
            <x-ui.label for="document_issued_at">Fecha de expedición</x-ui.label>
            <x-ui.input id="document_issued_at" type="date" name="document_issued_at" :value="old('document_issued_at', $employee?->document_issued_at?->toDateString())" />
            <x-ui.field-error :messages="$errors->get('document_issued_at')" />
        </div>
    </div>

    <div>
        <x-ui.label for="first_names">Nombres</x-ui.label>
        <x-ui.input id="first_names" type="text" name="first_names" :value="old('first_names', $employee?->first_names)" required />
        <x-ui.field-error :messages="$errors->get('first_names')" />
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-ui.label for="last_name_paternal">Apellido paterno</x-ui.label>
            <x-ui.input id="last_name_paternal" type="text" name="last_name_paternal" :value="old('last_name_paternal', $employee?->last_name_paternal)" />
            <x-ui.field-error :messages="$errors->get('last_name_paternal')" />
        </div>
        <div>
            <x-ui.label for="last_name_maternal">Apellido materno</x-ui.label>
            <x-ui.input id="last_name_maternal" type="text" name="last_name_maternal" :value="old('last_name_maternal', $employee?->last_name_maternal)" />
            <p class="mt-1 text-xs text-slate-500">Al menos uno de los dos. Si puedes, llena ambos.</p>
            <x-ui.field-error :messages="$errors->get('last_name_maternal')" />
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-ui.label for="sex">Sexo</x-ui.label>
            <select name="sex" id="sex" required class="{{ $selectClass }}">
                <option value="">Seleccione…</option>
                @foreach ($sexOptions as $value => $label)
                    <option value="{{ $value }}" @selected(old('sex', $employee?->sex?->value) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-ui.field-error :messages="$errors->get('sex')" />
        </div>
        <div>
            <x-ui.label for="birth_date">Fecha de nacimiento</x-ui.label>
            <x-ui.input id="birth_date" type="date" name="birth_date" :value="old('birth_date', $employee?->birth_date?->toDateString())" required />
            <x-ui.field-error :messages="$errors->get('birth_date')" />
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-ui.label for="birth_department">Departamento de nacimiento</x-ui.label>
            <select name="birth_department" id="birth_department" x-model="birthDepartment" @change="syncBirthCity()" class="{{ $selectClass }}">
                <option value="">Seleccione…</option>
                <template x-for="dept in departments" :key="'birth-'+dept">
                    <option :value="dept" x-text="dept"></option>
                </template>
            </select>
            <x-ui.field-error :messages="$errors->get('birth_department')" />
        </div>
        <div>
            <x-ui.label for="birth_city">Municipio de nacimiento</x-ui.label>
            <select name="birth_city" id="birth_city" x-model="birthCity" :disabled="!birthDepartment" class="{{ $selectClass }}">
                <option value="">Seleccione…</option>
                <template x-for="city in birthCities" :key="'birth-city-'+city">
                    <option :value="city" x-text="city"></option>
                </template>
            </select>
            <x-ui.field-error :messages="$errors->get('birth_city')" />
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-ui.label for="nationality">Nacionalidad</x-ui.label>
            <x-ui.input id="nationality" type="text" name="nationality" :value="old('nationality', $employee?->nationality ?? 'COLOMBIANA')" required />
            <x-ui.field-error :messages="$errors->get('nationality')" />
        </div>
        <div>
            <x-ui.label for="blood_group">Grupo sanguíneo</x-ui.label>
            <select name="blood_group" id="blood_group" required class="{{ $selectClass }}">
                <option value="">Seleccione…</option>
                @foreach ($bloodGroups as $value => $label)
                    <option value="{{ $value }}" @selected(old('blood_group', $employee?->blood_group?->value) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-ui.field-error :messages="$errors->get('blood_group')" />
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <x-ui.label for="education">Escolaridad</x-ui.label>
            <x-ui.input id="education" type="text" name="education" :value="old('education', $employee?->education)" />
            <x-ui.field-error :messages="$errors->get('education')" />
        </div>
        <div>
            <x-ui.label for="marital_status">Estado civil</x-ui.label>
            <x-ui.input id="marital_status" type="text" name="marital_status" :value="old('marital_status', $employee?->marital_status)" />
            <x-ui.field-error :messages="$errors->get('marital_status')" />
        </div>
        <div>
            <x-ui.label for="children_count">Número de hijos</x-ui.label>
            <x-ui.input id="children_count" type="number" name="children_count" min="0" max="30" :value="old('children_count', $employee?->children_count)" />
            <x-ui.field-error :messages="$errors->get('children_count')" />
        </div>
    </div>

    <label class="inline-flex items-center gap-2 text-sm text-slate-300">
        <input type="hidden" name="has_disability" value="0">
        <input type="checkbox" name="has_disability" value="1" @checked(old('has_disability', $employee?->has_disability)) class="rounded border-slate-600 bg-slate-950 text-indigo-600">
        Tiene discapacidad
    </label>

    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 pt-2">Contacto y residencia</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-ui.label for="phone">Teléfono</x-ui.label>
            <x-ui.input id="phone" type="text" name="phone" :value="old('phone', $employee?->phone)" />
            <x-ui.field-error :messages="$errors->get('phone')" />
        </div>
        <div>
            <x-ui.label for="email">Correo</x-ui.label>
            <x-ui.input id="email" type="email" name="email" :value="old('email', $employee?->email)" required />
            <x-ui.field-error :messages="$errors->get('email')" />
        </div>
        <div>
            <x-ui.label for="residence_city">Lugar de residencia</x-ui.label>
            <x-ui.input id="residence_city" type="text" name="residence_city" :value="old('residence_city', $employee?->residence_city)" />
            <x-ui.field-error :messages="$errors->get('residence_city')" />
        </div>
        <div>
            <x-ui.label for="address">Dirección</x-ui.label>
            <x-ui.input id="address" type="text" name="address" :value="old('address', $employee?->address)" />
            <x-ui.field-error :messages="$errors->get('address')" />
        </div>
        <div>
            <x-ui.label for="emergency_phone">Teléfono de emergencia</x-ui.label>
            <x-ui.input id="emergency_phone" type="text" name="emergency_phone" :value="old('emergency_phone', $employee?->emergency_phone)" />
            <x-ui.field-error :messages="$errors->get('emergency_phone')" />
        </div>
        <div>
            <x-ui.label for="emergency_contact">Contacto de emergencia</x-ui.label>
            <x-ui.input id="emergency_contact" type="text" name="emergency_contact" :value="old('emergency_contact', $employee?->emergency_contact)" />
            <x-ui.field-error :messages="$errors->get('emergency_contact')" />
        </div>
    </div>

    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 pt-2">Vinculación laboral</p>
    <div x-show="catalogNotice" x-cloak class="rounded-lg border border-emerald-500/40 bg-emerald-950/40 px-3 py-2 text-sm text-emerald-100" x-text="catalogNotice"></div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-ui.label for="collaborator_type_id">Tipo de colaborador</x-ui.label>
            <select
                name="collaborator_type_id"
                id="collaborator_type_id"
                required
                x-ref="typeSelect"
                x-model="selectedType"
                @mousedown="openCatalog($event)"
                class="{{ $selectClass }}"
            >
                <option value="" x-text="typesEmpty ? 'Crea primero el tipo…' : 'Seleccione…'"></option>
                <template x-for="type in types" :key="'type-'+type.id">
                    <option :value="String(type.id)" x-text="type.name"></option>
                </template>
            </select>
            <p x-show="typesEmpty" class="mt-1 text-[11px] text-amber-400">El tipo de colaborador se debe crear primero para este formulario.</p>
            <x-ui.field-error :messages="$errors->get('collaborator_type_id')" />
        </div>
        <div>
            <x-ui.label for="job_title_id">Cargo</x-ui.label>
            <select
                name="job_title_id"
                id="job_title_id"
                required
                x-ref="titleSelect"
                x-model="selectedTitle"
                @mousedown="openCatalog($event)"
                class="{{ $selectClass }}"
            >
                <option value="" x-text="titlesEmpty ? 'Crea primero el cargo…' : 'Seleccione…'"></option>
                <template x-for="title in titles" :key="'title-'+title.id">
                    <option :value="String(title.id)" x-text="title.name"></option>
                </template>
            </select>
            <p x-show="titlesEmpty" class="mt-1 text-[11px] text-amber-400">El cargo del colaborador se debe crear primero para este formulario.</p>
            <x-ui.field-error :messages="$errors->get('job_title_id')" />
        </div>
        <div>
            <x-ui.label for="engagement_type">Tipo de vinculación</x-ui.label>
            <x-ui.input id="engagement_type" type="text" name="engagement_type" :value="old('engagement_type', $employee?->engagement_type)" />
            <x-ui.field-error :messages="$errors->get('engagement_type')" />
        </div>
        <div>
            <x-ui.label for="contributor_type">Tipo de cotizante</x-ui.label>
            <x-ui.input id="contributor_type" type="text" name="contributor_type" :value="old('contributor_type', $employee?->contributor_type)" />
            <x-ui.field-error :messages="$errors->get('contributor_type')" />
        </div>
        <div>
            <x-ui.label for="labor_contract_type">Tipo de contrato</x-ui.label>
            <x-ui.input id="labor_contract_type" type="text" name="labor_contract_type" :value="old('labor_contract_type', $employee?->labor_contract_type)" />
            <x-ui.field-error :messages="$errors->get('labor_contract_type')" />
        </div>
        <div>
            <x-ui.label for="same_cost_center">Mismo centro de costo</x-ui.label>
            <select name="same_cost_center" id="same_cost_center" class="{{ $selectClass }}">
                @php
                    $sameCost = old('same_cost_center', $employee?->same_cost_center);
                    $sameCost = $sameCost === true || $sameCost === 1 || $sameCost === '1' ? '1' : ($sameCost === false || $sameCost === 0 || $sameCost === '0' ? '0' : '');
                @endphp
                <option value="" @selected($sameCost === '')>—</option>
                <option value="1" @selected($sameCost === '1')>Sí</option>
                <option value="0" @selected($sameCost === '0')>No</option>
            </select>
            <x-ui.field-error :messages="$errors->get('same_cost_center')" />
        </div>
        <div>
            <x-ui.label for="hired_on">Fecha de ingreso</x-ui.label>
            <x-ui.input id="hired_on" type="date" name="hired_on" :value="old('hired_on', $employee?->hired_on?->toDateString())" />
            <x-ui.field-error :messages="$errors->get('hired_on')" />
        </div>
        <div>
            <x-ui.label for="labor_contract_ends_on">Vencimiento de contrato</x-ui.label>
            <x-ui.input id="labor_contract_ends_on" type="date" name="labor_contract_ends_on" :value="old('labor_contract_ends_on', $employee?->labor_contract_ends_on?->toDateString())" />
            <x-ui.field-error :messages="$errors->get('labor_contract_ends_on')" />
        </div>
        <div>
            <x-ui.label for="left_on">Fecha de retiro</x-ui.label>
            <x-ui.input id="left_on" type="date" name="left_on" :value="old('left_on', $employee?->left_on?->toDateString())" />
            <x-ui.field-error :messages="$errors->get('left_on')" />
        </div>
    </div>

    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 pt-2">Seguridad social</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-ui.label for="eps_code">Código EPS</x-ui.label>
            <x-ui.input id="eps_code" type="text" name="eps_code" :value="old('eps_code', $employee?->eps_code)" />
            <x-ui.field-error :messages="$errors->get('eps_code')" />
        </div>
        <div>
            <x-ui.label for="eps_name">EPS</x-ui.label>
            <x-ui.input id="eps_name" type="text" name="eps_name" :value="old('eps_name', $employee?->eps_name)" />
            <x-ui.field-error :messages="$errors->get('eps_name')" />
        </div>
        <div>
            <x-ui.label for="afp_code">Código AFP</x-ui.label>
            <x-ui.input id="afp_code" type="text" name="afp_code" :value="old('afp_code', $employee?->afp_code)" />
            <x-ui.field-error :messages="$errors->get('afp_code')" />
        </div>
        <div>
            <x-ui.label for="afp_name">Pensión</x-ui.label>
            <x-ui.input id="afp_name" type="text" name="afp_name" :value="old('afp_name', $employee?->afp_name)" />
            <x-ui.field-error :messages="$errors->get('afp_name')" />
        </div>
        <div>
            <x-ui.label for="compensation_fund">Caja de compensación</x-ui.label>
            <x-ui.input id="compensation_fund" type="text" name="compensation_fund" :value="old('compensation_fund', $employee?->compensation_fund)" />
            <x-ui.field-error :messages="$errors->get('compensation_fund')" />
        </div>
        <div>
            <x-ui.label for="arl_name">ARL</x-ui.label>
            <x-ui.input id="arl_name" type="text" name="arl_name" :value="old('arl_name', $employee?->arl_name)" />
            <x-ui.field-error :messages="$errors->get('arl_name')" />
        </div>
        <div>
            <x-ui.label for="arl_risk_level">Nivel de riesgo ARL</x-ui.label>
            <x-ui.input id="arl_risk_level" type="text" name="arl_risk_level" :value="old('arl_risk_level', $employee?->arl_risk_level)" />
            <x-ui.field-error :messages="$errors->get('arl_risk_level')" />
        </div>
    </div>

    <div
        x-show="catalogOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 px-4"
        @click.self="closeCatalog()"
        @keydown.escape.window="closeCatalog()"
    >
        <div class="w-full max-w-md rounded-xl border border-slate-700 bg-slate-900 p-5 shadow-2xl space-y-4" @click.stop>
            <div class="space-y-1 text-center">
                <p class="text-sm font-semibold text-white">Crea el primer tipo y el primer cargo</p>
                <p class="text-xs text-slate-400">Tipo de colaborador y cargo del colaborador se deben crear primero para este formulario. Al guardar, puedes seleccionarlos sin perder lo que ya escribiste.</p>
            </div>

            <div x-show="typesEmpty" class="space-y-1">
                <x-ui.label for="catalog_type_name">Tipo de colaborador</x-ui.label>
                <x-ui.input id="catalog_type_name" type="text" x-model="newTypeName" placeholder="Ej. OPERATIVO" />
            </div>
            <div x-show="titlesEmpty" class="space-y-1">
                <x-ui.label for="catalog_title_name">Cargo</x-ui.label>
                <x-ui.input id="catalog_title_name" type="text" x-model="newTitleName" placeholder="Ej. Administrador" />
            </div>

            <p x-show="catalogError" class="text-sm text-red-400" x-text="catalogError"></p>

            <div class="flex items-center justify-end gap-2 pt-1">
                <button type="button" class="h-9 px-3 rounded-lg bg-slate-800 text-xs font-semibold text-slate-200" @click="closeCatalog()">Cancelar</button>
                <button type="button" class="h-9 px-3 rounded-lg bg-indigo-600 text-xs font-semibold text-white disabled:opacity-50" @click="saveCatalog()" :disabled="catalogLoading">
                    <span x-text="catalogLoading ? 'Guardando…' : 'Guardar'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
