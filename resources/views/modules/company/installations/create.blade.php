<x-company-layout title="Nueva instalación">
    <x-slot:actions>
        <x-ui.button variant="secondary" :href="route('company.installations.index')" size="sm">← Volver al listado</x-ui.button>
    </x-slot:actions>

    <div class="max-w-2xl">
        <form method="POST" action="{{ route('company.installations.store') }}" class="space-y-4 rounded-lg border border-slate-800 bg-slate-900/80 p-4">
            @csrf
            @if ($errors->any())
                <div class="rounded-lg border border-red-800 bg-red-950/40 px-3 py-2 text-sm text-red-200">
                    <ul class="list-disc pl-4 space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @include('modules.company.installations.partials.form', [
                'installation' => null,
                'clients' => $clients,
                'siteAdmins' => $siteAdmins,
            ])
            <x-ui.button type="submit" size="sm">Crear instalación</x-ui.button>
        </form>
    </div>
</x-company-layout>
