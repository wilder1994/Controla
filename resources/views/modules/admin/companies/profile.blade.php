<x-admin-layout :title="'Empresa: '.$company->displayName()">
    @include('modules.admin.companies.partials.nav-slots', [
        'company' => $company,
        'companyNavActive' => 'profile',
    ])

    <div class="max-w-2xl space-y-4">
        <p class="text-xs text-slate-500">Datos legales, contacto y ubicación para mapa y expediente.</p>
        @include('modules.shared.company-profile-form', [
            'company' => $company,
            'accent' => 'platform',
            'formAction' => route('admin.companies.profile.update', $company),
            'cancelUrl' => route('admin.companies.show', $company),
        ])
    </div>
</x-admin-layout>
