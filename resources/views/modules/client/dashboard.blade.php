<x-client-layout title="Resumen">
    <div class="space-y-6">
        <h2 class="text-2xl font-bold text-white">Panel del conjunto</h2>
        <p class="text-slate-400 text-sm">Gestión de censo y estructura — Fase 1 Controla.</p>
        <div class="grid sm:grid-cols-3 gap-4">
            <div class="rounded-xl border border-slate-800 bg-slate-900 p-5">
                <p class="text-xs uppercase text-slate-500">Unidades hoja</p>
                <p class="text-3xl font-bold text-white mt-1">{{ $units }}</p>
            </div>
            <a href="{{ route('client.structures.index') }}" class="rounded-xl border border-teal-800/50 bg-teal-950/30 p-5 hover:bg-teal-900/30">
                <p class="text-sm font-medium text-teal-200">Ir a Residencial →</p>
            </a>
            <a href="{{ route('client.members.index') }}" class="rounded-xl border border-indigo-800/50 bg-indigo-950/30 p-5 hover:bg-indigo-900/30">
                <p class="text-sm font-medium text-indigo-200">Ir a Personas →</p>
            </a>
        </div>
    </div>
</x-client-layout>
