@php
    $activeTab = $companyNavActive ?? 'cargos';
@endphp

<x-slot:headerTabs>
    <a href="{{ route('company.job-titles.index') }}" @class(['admin-header-tab', 'is-active' => $activeTab === 'cargos'])>Cargos</a>
    <a href="{{ route('company.collaborator-types.index') }}" @class(['admin-header-tab', 'is-active' => $activeTab === 'tipos'])>Tipos</a>
    <a href="{{ route('company.supervision-zones.index') }}" @class(['admin-header-tab', 'is-active' => $activeTab === 'zonas'])>Zonas</a>
    <a href="{{ route('company.supervision-shifts.index') }}" @class(['admin-header-tab', 'is-active' => $activeTab === 'turnos'])>Turnos</a>
    <a href="{{ route('company.supervision-preop.index') }}" @class(['admin-header-tab', 'is-active' => $activeTab === 'preop'])>Preoperacional</a>
</x-slot:headerTabs>
