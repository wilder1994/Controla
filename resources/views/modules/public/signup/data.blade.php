@php
    use App\Enums\PartyType;
    $fmt = fn (float $n) => '$'.number_format($n, 0, ',', '.');
@endphp

@extends('layouts.public')

@section('content')
    @include('modules.public.signup._steps', ['step' => 1])

    <div class="max-w-xl space-y-4">
        <p class="text-sm text-slate-400">Plan: <span class="text-white">{{ $intent->packageLabel() }}</span> · {{ $intent->billing_cycle->label() }} · {{ $fmt((float) $intent->amount) }}</p>

        <form method="POST" action="{{ route('signup.data.store', $intent) }}" class="space-y-4 rounded-xl border border-slate-800 bg-slate-900/80 p-4">
            @csrf
            <div>
                <x-ui.label for="party_type">Tipo de suscriptor</x-ui.label>
                <select name="party_type" id="party_type" class="w-full h-9 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white">
                    @foreach (PartyType::cases() as $type)
                        <option value="{{ $type->value }}" @selected(old('party_type', $intent->party_type?->value ?? PartyType::LegalEntity->value) === $type->value)>
                            {{ $type->label() }}
                        </option>
                    @endforeach
                </select>
                <x-ui.field-error name="party_type" />
            </div>
            <div>
                <x-ui.label for="legal_name">Razón social / nombre completo</x-ui.label>
                <x-ui.input id="legal_name" name="legal_name" value="{{ old('legal_name', $intent->legal_name) }}" required />
                <x-ui.field-error name="legal_name" />
            </div>
            <div>
                <x-ui.label for="trade_name">Nombre comercial (opcional)</x-ui.label>
                <x-ui.input id="trade_name" name="trade_name" value="{{ old('trade_name', $intent->trade_name) }}" />
                <x-ui.field-error name="trade_name" />
            </div>
            <div>
                <x-ui.label for="tax_id">NIT / documento fiscal</x-ui.label>
                <x-ui.input id="tax_id" name="tax_id" value="{{ old('tax_id', $intent->tax_id) }}" required />
                <x-ui.field-error name="tax_id" />
            </div>
            <div>
                <x-ui.label for="admin_name">Nombre del administrador</x-ui.label>
                <x-ui.input id="admin_name" name="admin_name" value="{{ old('admin_name', $intent->admin_name) }}" required />
                <x-ui.field-error name="admin_name" />
            </div>
            <div>
                <x-ui.label for="email">Email de acceso</x-ui.label>
                <x-ui.input type="email" id="email" name="email" value="{{ old('email', $intent->email) }}" required />
                <x-ui.field-error name="email" />
            </div>
            <div>
                <x-ui.label for="phone">Teléfono</x-ui.label>
                <x-ui.input id="phone" name="phone" value="{{ old('phone', $intent->phone) }}" />
                <x-ui.field-error name="phone" />
            </div>
            <x-ui.geo-address-fields
                :address="old('address', $intent->address)"
                :city="old('city', $intent->city)"
                :department="old('department', $intent->department)"
                :latitude="old('latitude', $intent->latitude)"
                :longitude="old('longitude', $intent->longitude)"
            />
            <div>
                <x-ui.label for="password">Contraseña</x-ui.label>
                <x-ui.input type="password" id="password" name="password" required />
                <x-ui.field-error name="password" />
            </div>
            <div>
                <x-ui.label for="password_confirmation">Confirmar contraseña</x-ui.label>
                <x-ui.input type="password" id="password_confirmation" name="password_confirmation" required />
            </div>
            <x-ui.button type="submit" class="w-full">Continuar → Aceptación legal</x-ui.button>
        </form>
    </div>
@endsection
