@php
    $employee = $employee ?? null;
    $selectClass = 'w-full h-9 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/30';
@endphp

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
        <x-ui.label for="collaborator_type_id">Tipo de colaborador</x-ui.label>
        <select name="collaborator_type_id" id="collaborator_type_id" required class="{{ $selectClass }}">
            <option value="">Seleccione…</option>
            @foreach ($collaboratorTypes as $type)
                <option value="{{ $type->id }}" @selected((string) old('collaborator_type_id', $employee?->collaborator_type_id) === (string) $type->id)>{{ $type->name }}</option>
            @endforeach
        </select>
        @if ($collaboratorTypes->isEmpty())
            <p class="mt-1 text-[11px] text-amber-400">Crea un tipo en Ajustes → Tipos antes de dar de alta.</p>
        @endif
        <x-ui.field-error :messages="$errors->get('collaborator_type_id')" />
    </div>
    <div>
        <x-ui.label for="job_title_id">Cargo</x-ui.label>
        <select name="job_title_id" id="job_title_id" required class="{{ $selectClass }}">
            <option value="">Seleccione…</option>
            @foreach ($jobTitles as $title)
                <option value="{{ $title->id }}" @selected((string) old('job_title_id', $employee?->job_title_id) === (string) $title->id)>{{ $title->name }}</option>
            @endforeach
        </select>
        @if ($jobTitles->isEmpty())
            <p class="mt-1 text-[11px] text-amber-400">Crea un cargo en Ajustes → Cargos antes de dar de alta.</p>
        @endif
        <x-ui.field-error :messages="$errors->get('job_title_id')" />
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <x-ui.label for="email">Correo</x-ui.label>
        <x-ui.input id="email" type="email" name="email" :value="old('email', $employee?->email)" required />
        <x-ui.field-error :messages="$errors->get('email')" />
    </div>
    <div>
        <x-ui.label for="nationality">Nacionalidad</x-ui.label>
        <x-ui.input id="nationality" type="text" name="nationality" :value="old('nationality', $employee?->nationality ?? 'COLOMBIANA')" required />
        <x-ui.field-error :messages="$errors->get('nationality')" />
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <x-ui.label for="birth_department">Departamento de nacimiento</x-ui.label>
        <x-ui.input id="birth_department" type="text" name="birth_department" :value="old('birth_department', $employee?->birth_department)" />
        <x-ui.field-error :messages="$errors->get('birth_department')" />
    </div>
    <div>
        <x-ui.label for="birth_city">Ciudad de nacimiento</x-ui.label>
        <x-ui.input id="birth_city" type="text" name="birth_city" :value="old('birth_city', $employee?->birth_city)" />
        <x-ui.field-error :messages="$errors->get('birth_city')" />
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div>
        <x-ui.label for="document_issue_department">Depto. expedición documento</x-ui.label>
        <x-ui.input id="document_issue_department" type="text" name="document_issue_department" :value="old('document_issue_department', $employee?->document_issue_department)" />
        <x-ui.field-error :messages="$errors->get('document_issue_department')" />
    </div>
    <div>
        <x-ui.label for="document_issue_city">Ciudad expedición documento</x-ui.label>
        <x-ui.input id="document_issue_city" type="text" name="document_issue_city" :value="old('document_issue_city', $employee?->document_issue_city)" />
        <x-ui.field-error :messages="$errors->get('document_issue_city')" />
    </div>
    <div>
        <x-ui.label for="document_issued_at">Fecha expedición</x-ui.label>
        <x-ui.input id="document_issued_at" type="date" name="document_issued_at" :value="old('document_issued_at', $employee?->document_issued_at?->toDateString())" />
        <x-ui.field-error :messages="$errors->get('document_issued_at')" />
    </div>
</div>

<label class="inline-flex items-center gap-2 text-sm text-slate-300">
    <input type="hidden" name="has_disability" value="0">
    <input type="checkbox" name="has_disability" value="1" @checked(old('has_disability', $employee?->has_disability)) class="rounded border-slate-600 bg-slate-950 text-indigo-600">
    Tiene discapacidad
</label>
