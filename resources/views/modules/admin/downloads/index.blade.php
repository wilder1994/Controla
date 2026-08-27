<x-admin-layout title="Descargas">
    <p class="text-sm text-slate-400 mb-6">App de Supervisión de campo. El supervisor inicia sesión con su usuario de empresa.</p>
    @include('modules.downloads.partials.supervision-card', ['pwaUrl' => $pwaUrl, 'openClass' => 'bg-violet-600 hover:bg-violet-500'])
</x-admin-layout>
