@php
    use App\Support\Auth\AssignableRoles;
    $isEdit = $managedUser !== null;
    $selectedRole = old('role', $managedUser?->getRoleNames()->first());
    $selectedClients = old('client_ids', $managedUser?->clients->pluck('id')->all() ?? []);
    $requiresClients = in_array($selectedRole, AssignableRoles::requiringClientAssignment(), true);
    $accent = $accent ?? 'default';
    $focusClass = match ($accent) {
        'platform' => 'focus:border-violet-500 focus:ring-violet-500/30',
        'client' => 'focus:border-teal-500 focus:ring-teal-500/30',
        default => 'focus:border-indigo-500 focus:ring-indigo-500/30',
    };
@endphp

<div class="space-y-4">
    <div>
        <x-ui.label for="name">Nombre</x-ui.label>
        <x-ui.input id="name" name="name" :value="old('name', $managedUser?->name)" required :accent="$accent" />
        <x-ui.field-error :messages="$errors->get('name')" />
    </div>

    <div>
        <x-ui.label for="email">Email de acceso</x-ui.label>
        <x-ui.input type="email" id="email" name="email" :value="old('email', $managedUser?->email)" required :accent="$accent" />
        <x-ui.field-error :messages="$errors->get('email')" />
    </div>

    <div>
        <x-ui.label for="role">Rol</x-ui.label>
        <select name="role" id="role" required class="w-full h-9 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white focus:ring-1 {{ $focusClass }}">
            @foreach ($roleOptions as $role)
                <option value="{{ $role }}" @selected($selectedRole === $role)>{{ AssignableRoles::label($role) }}</option>
            @endforeach
        </select>
        <x-ui.field-error :messages="$errors->get('role')" />
    </div>

    @if (! empty($showCompanySelect) && $companies)
        <div>
            <x-ui.label for="security_company_id">Empresa</x-ui.label>
            <select name="security_company_id" id="security_company_id" class="w-full h-9 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white focus:border-violet-500 focus:ring-1 focus:ring-violet-500/30">
                <option value="">— Sin empresa —</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" @selected((int) old('security_company_id', $managedUser?->security_company_id) === (int) $company->id)>
                        {{ $company->trade_name ?: $company->legal_name }}
                    </option>
                @endforeach
            </select>
            <x-ui.field-error :messages="$errors->get('security_company_id')" />
        </div>
    @endif

    @if (isset($clients) && $clients->isNotEmpty())
        <div id="client-assignment-block" class="{{ $requiresClients ? '' : 'hidden' }}">
            <x-ui.label>Conjuntos asignados</x-ui.label>
            <div class="mt-2 space-y-2 rounded-lg border border-slate-800 bg-slate-950/50 p-3 max-h-48 overflow-y-auto">
                @foreach ($clients as $client)
                    <label class="flex items-center gap-2 text-sm text-slate-300">
                        <input type="checkbox" name="client_ids[]" value="{{ $client->id }}" @checked(in_array($client->id, $selectedClients, false)) class="rounded border-slate-600 bg-slate-950 text-indigo-600">
                        <span>{{ $client->name }}</span>
                        @if ($client->relationLoaded('securityCompany') && $client->securityCompany)
                            <span class="text-xs text-slate-500">({{ $client->securityCompany->trade_name }})</span>
                        @endif
                    </label>
                @endforeach
            </div>
            <x-ui.field-error :messages="$errors->get('client_ids')" />
        </div>
    @endif

    <div>
        <x-ui.label for="password">{{ $isEdit ? 'Nueva contraseña (opcional)' : 'Contraseña' }}</x-ui.label>
        <x-ui.input type="password" id="password" name="password" :required="! $isEdit" :accent="$accent" />
        <x-ui.field-error :messages="$errors->get('password')" />
    </div>

    <div>
        <x-ui.label for="password_confirmation">Confirmar contraseña</x-ui.label>
        <x-ui.input type="password" id="password_confirmation" name="password_confirmation" :accent="$accent" />
    </div>

    <label class="inline-flex items-center gap-2 text-sm text-slate-300">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $managedUser?->is_active ?? true)) class="rounded border-slate-600 bg-slate-950 text-indigo-600">
        Usuario activo
    </label>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const roleSelect = document.getElementById('role');
                const block = document.getElementById('client-assignment-block');
                if (!roleSelect || !block) return;
                const rolesNeedingClients = @json(AssignableRoles::requiringClientAssignment());
                const toggle = () => block.classList.toggle('hidden', !rolesNeedingClients.includes(roleSelect.value));
                roleSelect.addEventListener('change', toggle);
                toggle();
            });
        </script>
    @endpush
@endonce
