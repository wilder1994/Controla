@php
    $fmt = fn (?float $n) => $n !== null ? '$'.number_format($n, 0, ',', '.') : '—';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Checkout — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md rounded-xl border border-slate-800 bg-slate-900/90 p-6 shadow-xl">
        @if (session('warning'))
            <div class="mb-4 rounded-lg bg-amber-900/40 border border-amber-700 text-amber-200 px-4 py-3 text-sm">{{ session('warning') }}</div>
        @endif

        <div class="text-center">
            <p class="text-xs uppercase tracking-wider text-slate-500">Checkout simulado</p>
            <h1 class="text-lg font-semibold text-white mt-1">{{ $company?->displayName() }}</h1>
        </div>

        <div class="mt-6 rounded-lg border border-slate-800 bg-slate-950/60 p-4 space-y-2 text-sm">
            <div class="flex justify-between">
                <span class="text-slate-500">Monto</span>
                <span class="text-white font-medium tabular-nums">{{ $fmt((float) $payment->amount) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">Método</span>
                <span class="text-slate-300">Pasarela (local)</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">ID simulado</span>
                <span class="text-slate-500 font-mono text-xs">{{ \Illuminate\Support\Str::limit($payment->gateway_transaction_id, 20, '…') }}</span>
            </div>
        </div>

        <p class="mt-4 text-xs text-slate-500 text-center">
            Sin proveedor externo. Use Aprobar o Rechazar para simular el resultado del pago.
        </p>

        <div class="mt-6 grid grid-cols-2 gap-3">
            <form method="POST" action="{{ route('billing.checkout.reject', $payment) }}">
                @csrf
                <x-ui.button type="submit" variant="secondary" size="md" class="w-full">Rechazar</x-ui.button>
            </form>
            <form method="POST" action="{{ route('billing.checkout.approve', $payment) }}">
                @csrf
                <x-ui.button type="submit" variant="success" size="md" class="w-full">Aprobar pago</x-ui.button>
            </form>
        </div>

        <p class="mt-4 text-center">
            @can('platform.documents.manage')
                <a href="{{ route('admin.documents.expedientes.show', $company) }}" class="text-xs text-slate-500 hover:text-slate-300">Volver al expediente</a>
            @else
                <a href="{{ route('company.billing.index') }}" class="text-xs text-slate-500 hover:text-slate-300">Volver a facturación</a>
            @endcan
        </p>
    </div>
</body>
</html>
