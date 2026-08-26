@php
    $activeTab = $companyNavActive ?? 'cargos';
@endphp

<x-slot:headerTabs>
    <a
        href="{{ route('company.job-titles.index') }}"
        @class(['admin-header-tab', 'is-active' => $activeTab === 'cargos'])
    >
        Cargos
    </a>
    <a
        href="{{ route('company.collaborator-types.index') }}"
        @class(['admin-header-tab', 'is-active' => $activeTab === 'tipos'])
    >
        Tipos
    </a>
</x-slot:headerTabs>
