@php
    use App\Enums\PartyType;
@endphp

@props([
    'company',
    'accent' => 'default',
    'formAction',
    'cancelUrl',
])

<form method="POST" action="{{ $formAction }}" class="space-y-4 rounded-lg border border-slate-800 bg-slate-900/80 p-4">
    @csrf
    @method('PUT')

    <div>
        <x-ui.label for="party_type">Tipo de suscriptor</x-ui.label>
        <select
            name="party_type"
            id="party_type"
            class="w-full h-9 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white focus:ring-1 {{ $accent === 'platform' ? 'focus:border-violet-500 focus:ring-violet-500/30' : 'focus:border-indigo-500 focus:ring-indigo-500/30' }}"
        >
            @foreach (PartyType::cases() as $type)
                <option value="{{ $type->value }}" @selected(old('party_type', $company->party_type?->value) === $type->value)>
                    {{ $type->label() }}
                </option>
            @endforeach
        </select>
        <x-ui.field-error :messages="$errors->get('party_type')" />
    </div>

    <div>
        <x-ui.label for="legal_name">Razón social / nombre legal</x-ui.label>
        <x-ui.input id="legal_name" name="legal_name" :value="old('legal_name', $company->legal_name)" required :accent="$accent" />
        <x-ui.field-error :messages="$errors->get('legal_name')" />
    </div>

    <div>
        <x-ui.label for="trade_name">Nombre comercial</x-ui.label>
        <x-ui.input id="trade_name" name="trade_name" :value="old('trade_name', $company->trade_name)" :accent="$accent" />
        <x-ui.field-error :messages="$errors->get('trade_name')" />
    </div>

  @if ($company->hasCompletedAcceptance())
        <div>
            <x-ui.label>NIT / identificador fiscal</x-ui.label>
            <p class="text-sm text-slate-300">{{ $company->tax_id }}</p>
            <p class="text-xs text-slate-500 mt-1">No editable tras aceptación contractual.</p>
        </div>
    @else
        <div>
            <x-ui.label for="tax_id">NIT / identificador fiscal</x-ui.label>
            <x-ui.input id="tax_id" name="tax_id" :value="old('tax_id', $company->tax_id)" required :accent="$accent" />
            <x-ui.field-error :messages="$errors->get('tax_id')" />
        </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-ui.label for="email">Email comercial</x-ui.label>
            <x-ui.input type="email" id="email" name="email" :value="old('email', $company->email)" :accent="$accent" />
            <x-ui.field-error :messages="$errors->get('email')" />
        </div>
        <div>
            <x-ui.label for="phone">Teléfono</x-ui.label>
            <x-ui.input id="phone" name="phone" :value="old('phone', $company->phone)" :accent="$accent" />
            <x-ui.field-error :messages="$errors->get('phone')" />
        </div>
    </div>

    <x-ui.geo-address-fields
        :address="old('address', $company->address)"
        :latitude="old('latitude', $company->latitude)"
        :longitude="old('longitude', $company->longitude)"
        :accent="$accent"
    />

    <div class="flex items-center gap-3 pt-2">
        <x-ui.button type="submit" :variant="$accent === 'platform' ? 'platform' : 'primary'">Guardar datos</x-ui.button>
        <a href="{{ $cancelUrl }}" class="text-sm text-slate-400 hover:text-white">Cancelar</a>
    </div>
</form>
