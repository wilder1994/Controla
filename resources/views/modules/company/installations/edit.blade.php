<x-company-layout title="Editar instalación">
    <x-slot:actions>
        <x-ui.button variant="secondary" :href="route('company.installations.show', $installation)" size="sm">← Ficha</x-ui.button>
    </x-slot:actions>

    <div class="max-w-2xl">
        <form method="POST" action="{{ route('company.installations.update', $installation) }}" class="space-y-4 rounded-lg border border-slate-800 bg-slate-900/80 p-4">
            @csrf
            @method('PUT')
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
                'installation' => $installation,
                'clients' => $clients,
                'siteAdmins' => $siteAdmins,
            ])
            <x-ui.button type="submit" size="sm">Guardar</x-ui.button>
        </form>
    </div>
</x-company-layout>
