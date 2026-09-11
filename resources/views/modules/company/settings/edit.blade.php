<x-company-layout title="Mis datos">
    <div class="max-w-2xl space-y-4">
        <p class="text-sm text-slate-400">Datos legales, contacto, ubicación, logo y texto de las fichas de revista.</p>
        @include('modules.shared.company-profile-form', [
            'company' => $company,
            'accent' => 'default',
            'formAction' => route('company.settings.update'),
            'cancelUrl' => route('company.dashboard'),
            'logoPreviewUrl' => $logoPreviewUrl ?? null,
        ])
    </div>
</x-company-layout>
