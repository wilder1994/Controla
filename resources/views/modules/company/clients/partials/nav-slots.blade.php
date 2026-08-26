@php
    $activeTab = $clientNavActive ?? 'cliente';
    $vista = $vista ?? $activeTab;
    $statusTone = $client->is_active ? 'text-emerald-400' : 'text-slate-500';
    $locationBits = array_filter([(string) $client->city, (string) $client->department]);
    $locationLabel = $locationBits !== [] ? implode(', ', $locationBits) : null;
@endphp

<x-slot:subtitle>
    <span class="text-slate-500">
        {{ $client->slug }}
        @if ($locationLabel)
            · {{ $locationLabel }}
        @endif
        · {{ $client->securityCompany?->package_modality?->label() ?? 'Modalidad N/D' }}
    </span>
    <span class="{{ $statusTone }} font-medium pl-3 sm:pl-4">
        {{ $client->is_active ? 'Activo' : 'Inactivo' }}
    </span>
    @if ($client->has_access)
        <span class="text-indigo-300 font-medium pl-3">Accesos</span>
    @endif
    @if ($client->has_supervision)
        <span class="text-amber-300 font-medium pl-3">Pro</span>
    @endif
    @if ($client->isCatalogOnly())
        <span class="text-slate-500 font-medium pl-3">Solo ficha</span>
    @endif
</x-slot:subtitle>

<x-slot:actions>
    <x-ui.button variant="secondary" :href="route('company.clients.index')" size="sm">← Cartera</x-ui.button>
    @can('company.clients.manage')
        <x-ui.button :href="route('company.clients.create')" size="sm">+ Cliente</x-ui.button>
    @endcan
</x-slot:actions>

<x-slot:headerTabs>
    <a
        href="{{ route('company.clients.show', [$client, 'vista' => 'cliente']) }}"
        @class(['admin-header-tab', 'is-active' => $vista === 'cliente' && $activeTab !== 'editar'])
    >
        Cliente
    </a>

    @if ($client->has_access)
        <a
            href="{{ route('company.clients.show', [$client, 'vista' => 'accesos']) }}"
            @class(['admin-header-tab', 'is-active' => $vista === 'accesos' && $activeTab !== 'editar'])
        >
            Accesos
        </a>
    @endif

    @if ($client->has_supervision)
        <a
            href="{{ route('company.clients.show', [$client, 'vista' => 'supervision']) }}"
            @class(['admin-header-tab', 'is-active' => $vista === 'supervision' && $activeTab !== 'editar'])
        >
            Supervisión
        </a>
    @endif

    @if ($canOperate ?? false)
        <form method="POST" action="{{ route('company.clients.activate', $client) }}" class="inline-flex">
            @csrf
            <button type="submit" class="admin-header-tab">Operar portería</button>
        </form>
    @endif

    @if ($canOperateClientPanel ?? false)
        <form method="POST" action="{{ route('company.clients.operate-client', $client) }}" class="inline-flex">
            @csrf
            <button type="submit" class="admin-header-tab">Operar cliente</button>
        </form>
    @endif

    @if ($canUpdate ?? false)
        <a
            href="{{ route('company.clients.edit', $client) }}"
            @class(['admin-header-tab', 'is-active' => $activeTab === 'editar'])
        >
            Editar
        </a>
    @endif
</x-slot:headerTabs>
