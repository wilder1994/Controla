<x-company-layout title="Descargas">
    <p class="text-sm text-slate-400 mb-6">Instale la app de Supervisión en el celular del supervisor. No es la app de residentes de Accesos.</p>
    @include('modules.downloads.partials.supervision-card', ['pwaUrl' => $pwaUrl, 'openClass' => 'bg-indigo-600 hover:bg-indigo-500'])
</x-company-layout>
