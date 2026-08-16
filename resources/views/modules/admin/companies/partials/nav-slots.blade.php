{{-- Slots de nav compartidos: show / profile / expediente de una empresa --}}
@php
    use App\Enums\SubscriptionStatus;

    $statusLabel = $company->subscription_status?->label() ?? ($company->is_active ? 'Activa' : 'Inactiva');
    $isActiveStatus = $company->is_active
        && ($company->subscription_status === null || $company->subscription_status === SubscriptionStatus::Active);
    $statusTone = $isActiveStatus ? 'text-emerald-400' : 'text-amber-300';
    $locationBits = array_filter([(string) $company->city, (string) $company->department]);
    $locationLabel = $locationBits !== [] ? implode(', ', $locationBits) : null;
    $activeTab = $companyNavActive ?? 'show';
@endphp

<x-slot:subtitle>
    <span class="text-slate-500">
        NIT {{ $company->tax_id }}
        @if ($locationLabel)
            · {{ $locationLabel }}
        @endif
    </span>
    <span class="{{ $statusTone }} font-medium pl-3 sm:pl-4">{{ $statusLabel }}</span>
</x-slot:subtitle>

<x-slot:actions>
    <x-ui.button variant="secondary" :href="route('admin.companies.index')" size="sm">← Empresas</x-ui.button>
</x-slot:actions>

<x-slot:headerTabs>
    <a
        href="{{ route('admin.companies.show', $company) }}"
        @class(['admin-header-tab', 'is-active' => $activeTab === 'show'])
    >
        Resumen
    </a>

    @can('updateProfile', $company)
        <a
            href="{{ route('admin.companies.profile.edit', $company) }}"
            @class(['admin-header-tab', 'is-active' => $activeTab === 'profile'])
        >
            Perfil y ubicación
        </a>
    @endcan

    @can('platform.documents.view')
        <a
            href="{{ route('admin.documents.expedientes.show', $company) }}"
            @class(['admin-header-tab', 'is-active' => $activeTab === 'expediente'])
        >
            Expediente docs
        </a>
    @endcan

    @can('platform.companies.view')
        <a
            href="{{ route('admin.companies.historial', $company) }}"
            @class(['admin-header-tab', 'is-active' => $activeTab === 'historial'])
        >
            Historial
        </a>
    @endcan

    @can('platform.companies.manage')
        <form method="POST" action="{{ route('admin.companies.enter', $company) }}" class="inline-flex">
            @csrf
            <button type="submit" class="admin-header-tab">
                Entrar como empresa
            </button>
        </form>
    @endcan
</x-slot:headerTabs>
