<x-client-layout title="Resumen">
    <h2 class="text-2xl font-bold text-white mb-1">Panel del cliente</h2>
    <p class="text-slate-400 text-sm mb-4">Instalaciones, puestos, novedades y salud afiliatoria.</p>
    @include('modules.ops.sig-board', ['sigBoard' => $sigBoard, 'compact' => false, 'sigLiveUrl' => route('client.sig.live')])
</x-client-layout>
