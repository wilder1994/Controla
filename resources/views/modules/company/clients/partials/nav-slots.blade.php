@php
    $activeTab = $clientNavActive ?? 'resumen';
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
</x-slot:subtitle>

<x-slot:actions>
    <x-ui.button variant="secondary" :href="route('company.clients.index')" size="sm">← Cartera</x-ui.button>
    @can('company.clients.manage')
        @if (! ($companyContext['is_quota_full'] ?? true))
            <x-ui.button :href="route('company.clients.create')" size="sm">+ Cliente</x-ui.button>
        @endif
    @endcan
</x-slot:actions>

<x-slot:headerTabs>
    <a
        href="{{ route('company.clients.show', $client) }}"
        @class(['admin-header-tab', 'is-active' => $activeTab === 'resumen'])
    >
        Resumen
    </a>

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
