@php
    use App\Enums\BillingCycle;
    use App\Enums\CompanyPackageSku;
    use App\Enums\PartyType;
@endphp

<x-admin-layout title="Crear empresa">
    <div class="max-w-2xl space-y-4">
        <x-ui.button variant="secondary" :href="route('admin.companies.index')" size="sm">← Empresas</x-ui.button>

        <form method="POST" action="{{ route('admin.companies.store') }}" class="space-y-4 rounded-lg border border-slate-800 bg-slate-900/80 p-4">
            @csrf

            <div>
                <x-ui.label for="party_type">Tipo de suscriptor</x-ui.label>
                <select name="party_type" id="party_type" class="w-full h-9 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white focus:border-violet-500 focus:ring-1 focus:ring-violet-500/30">
                    @foreach (PartyType::cases() as $type)
                        <option value="{{ $type->value }}" @selected(old('party_type', PartyType::LegalEntity->value) === $type->value)>
                            {{ $type->label() }}
                        </option>
                    @endforeach
                </select>
                <x-ui.field-error :messages="$errors->get('party_type')" />
            </div>

            <div>
                <x-ui.label for="legal_name">Razón social / nombre legal</x-ui.label>
                <x-ui.input id="legal_name" name="legal_name" :value="old('legal_name')" required accent="platform" />
                <x-ui.field-error :messages="$errors->get('legal_name')" />
            </div>

            <div>
                <x-ui.label for="trade_name">Nombre comercial</x-ui.label>
                <x-ui.input id="trade_name" name="trade_name" :value="old('trade_name')" accent="platform" />
                <x-ui.field-error :messages="$errors->get('trade_name')" />
            </div>

            <div>
                <x-ui.label for="tax_id">NIT / identificador fiscal</x-ui.label>
                <x-ui.input id="tax_id" name="tax_id" :value="old('tax_id')" required accent="platform" />
                <x-ui.field-error :messages="$errors->get('tax_id')" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-ui.label for="email">Email comercial</x-ui.label>
                    <x-ui.input type="email" id="email" name="email" :value="old('email')" accent="platform" />
                    <x-ui.field-error :messages="$errors->get('email')" />
                </div>
                <div>
                    <x-ui.label for="phone">Teléfono</x-ui.label>
                    <x-ui.input id="phone" name="phone" :value="old('phone')" accent="platform" />
                    <x-ui.field-error :messages="$errors->get('phone')" />
                </div>
            </div>

            <x-ui.geo-address-fields
                :address="old('address')"
                :city="old('city')"
                :department="old('department')"
                :latitude="old('latitude')"
                :longitude="old('longitude')"
                accent="platform"
            />

            <div class="border-t border-slate-800 pt-4 space-y-4">
                <p class="text-xs text-slate-500">Paquete inicial (opcional). Se puede asignar después.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-ui.label for="package_sku">Paquete</x-ui.label>
                        <select name="package_sku" id="package_sku" class="w-full h-9 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white focus:border-violet-500 focus:ring-1 focus:ring-violet-500/30">
                            <option value="">— Sin paquete —</option>
                            @foreach (CompanyPackageSku::options() as $value => $label)
                                <option value="{{ $value }}" @selected(old('package_sku') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-ui.field-error :messages="$errors->get('package_sku')" />
                    </div>
                    <div>
                        <x-ui.label for="billing_cycle">Ciclo</x-ui.label>
                        <select name="billing_cycle" id="billing_cycle" class="w-full h-9 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white focus:border-violet-500 focus:ring-1 focus:ring-violet-500/30">
                            @foreach (BillingCycle::options() as $value => $label)
                                <option value="{{ $value }}" @selected(old('billing_cycle', 'monthly') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-ui.field-error :messages="$errors->get('billing_cycle')" />
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <x-ui.button type="submit" variant="platform">Crear empresa</x-ui.button>
                <a href="{{ route('admin.companies.index') }}" class="text-sm text-slate-400 hover:text-white">Cancelar</a>
            </div>
        </form>
    </div>
</x-admin-layout>
