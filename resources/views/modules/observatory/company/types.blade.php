<x-company-layout title="Tipos del Observatorio">
    <x-slot:headerTabs>
        @include('modules.observatory.partials.nav-tabs', [
            'indexRoute' => 'company.observatory.events.index',
            'typesRoute' => 'company.observatory.types.index',
            'vista' => 'tipos',
        ])
    </x-slot:headerTabs>

    <form method="GET" action="{{ route('company.observatory.types.index') }}" class="mb-4 max-w-sm">
        <label for="client_id" class="block text-[11px] text-slate-500 mb-1">Cliente</label>
        <select id="client_id" name="client_id" onchange="this.form.submit()"
                class="w-full h-9 px-2 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white">
            <option value="">Elige un cliente</option>
            @foreach ($filterClients as $row)
                <option value="{{ $row->id }}" @selected((string) $filterClientId === (string) $row->id)>{{ $row->name }}</option>
            @endforeach
        </select>
    </form>

    @if (! $filterClientId)
        <p class="text-sm text-slate-400">Los tipos los define el admin de cada cliente. Elige uno para verlos.</p>
    @else
        @include('modules.observatory.partials.types-catalog', [
            'types' => $types,
            'nextColor' => $nextColor ?? '#94a3b8',
            'canEdit' => false,
            'storeRoute' => null,
            'updateRoute' => null,
            'destroyRoute' => null,
            'accent' => 'indigo',
        ])
    @endif
</x-company-layout>
