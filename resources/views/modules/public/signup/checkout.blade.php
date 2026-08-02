@php
    $fmt = fn (float $n) => '$'.number_format($n, 0, ',', '.');
@endphp

@extends('layouts.public')

@section('content')
    <div class="mx-auto max-w-md space-y-6">
        <div class="text-center">
            <p class="text-xs text-slate-500 uppercase tracking-widest">Checkout simulado</p>
            <h2 class="text-lg font-semibold text-white mt-1">{{ $intent->legal_name }}</h2>
            <p class="text-xs text-slate-500 mt-1">Sin proveedor externo · modo local</p>
        </div>

        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4 space-y-2 text-sm">
            <div class="flex justify-between">
                <span class="text-slate-500">Monto</span>
                <span class="font-semibold tabular-nums">{{ $fmt((float) $intent->amount) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">Plan</span>
                <span>{{ $intent->packageLabel() }}</span>
            </div>
        </div>

        <p class="text-xs text-slate-500 text-center">
            Simula el resultado del pago. Aprobar crea empresa, usuario y expediente. Rechazar no deja registro.
        </p>

        <div class="grid grid-cols-2 gap-3">
            <form method="POST" action="{{ route('signup.checkout.reject', $intent) }}">
                @csrf
                <x-ui.button type="submit" variant="secondary" class="w-full">Rechazar</x-ui.button>
            </form>
            <form method="POST" action="{{ route('signup.checkout.approve', $intent) }}">
                @csrf
                <x-ui.button type="submit" variant="success" class="w-full">Aprobar pago</x-ui.button>
            </form>
        </div>
    </div>
@endsection
