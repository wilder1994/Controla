<x-company-layout title="Mi empresa">
    <div class="max-w-2xl space-y-4">
        <p class="text-sm text-slate-400">Datos legales, contacto y ubicación de tu empresa de seguridad.</p>
        @include('modules.shared.company-profile-form', [
            'company' => $company,
            'accent' => 'default',
            'formAction' => route('company.settings.update'),
            'cancelUrl' => route('company.dashboard'),
        ])
    </div>
</x-company-layout>
