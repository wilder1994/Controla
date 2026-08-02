@php
    use App\Enums\PartyType;
@endphp

<x-admin-layout title="Expedientes">
    <div class="flex flex-col flex-1 min-h-0 gap-3">
        <div class="flex items-center justify-between gap-3 shrink-0">
            <div>
                <p class="text-xs text-slate-500">Documentos · Expedientes comerciales</p>
                <p class="text-xs text-slate-500 mt-1">Un expediente por empresa suscriptora: aceptación, pagos y evidencias.</p>
            </div>
            <x-ui.button variant="secondary" :href="route('admin.documents.index')" size="sm">← Documentos</x-ui.button>
        </div>

        <div class="flex-1 min-h-0 rounded-lg border border-slate-800 bg-slate-900/80 overflow-hidden flex flex-col">
            <div class="flex-1 overflow-y-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-950/60 text-xs uppercase tracking-wide text-slate-500 sticky top-0">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium">Suscriptor</th>
                            <th class="px-4 py-2 text-left font-medium">Tipo</th>
                            <th class="px-4 py-2 text-left font-medium">Aceptaciones</th>
                            <th class="px-4 py-2 text-left font-medium">Documentos</th>
                            <th class="px-4 py-2 text-left font-medium">Evidencias</th>
                            <th class="px-4 py-2 text-right font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @forelse ($companies as $company)
                            <tr class="hover:bg-slate-800/30">
                                <td class="px-4 py-2.5">
                                    <p class="font-medium text-slate-200">{{ $company->displayName() }}</p>
                                    <p class="text-xs text-slate-600">{{ $company->tax_id }}</p>
                                </td>
                                <td class="px-4 py-2.5 text-slate-400 text-xs">
                                    {{ $company->party_type?->label() ?? PartyType::LegalEntity->label() }}
                                </td>
                                <td class="px-4 py-2.5 tabular-nums text-slate-300">
                                    @if ($company->subscription_acceptances_count > 0)
                                        <span class="text-emerald-400">{{ $company->subscription_acceptances_count }}</span>
                                    @else
                                        <span class="text-amber-400">Pendiente</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 tabular-nums text-slate-300">{{ $company->platform_documents_count }}</td>
                                <td class="px-4 py-2.5 tabular-nums text-slate-300">{{ $company->lifecycle_evidence_events_count }}</td>
                                <td class="px-4 py-2.5 text-right">
                                    <x-ui.button variant="secondary" :href="route('admin.documents.expedientes.show', $company)" size="sm">
                                        Ver expediente
                                    </x-ui.button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-slate-500">No hay empresas registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin-layout>
