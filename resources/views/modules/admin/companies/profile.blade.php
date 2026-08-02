<x-admin-layout :title="'Perfil: '.$company->trade_name">
    <div class="max-w-2xl space-y-4">
        <x-ui.button variant="secondary" :href="route('admin.companies.show', $company)" size="sm">← Empresa</x-ui.button>
        <p class="text-xs text-slate-500">Datos legales, contacto y ubicación para mapa y expediente.</p>
        @include('modules.shared.company-profile-form', [
            'company' => $company,
            'accent' => 'platform',
            'formAction' => route('admin.companies.profile.update', $company),
            'cancelUrl' => route('admin.companies.show', $company),
        ])
    </div>
</x-admin-layout>
