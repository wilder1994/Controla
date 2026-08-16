@php
    use Illuminate\Support\Str;

    $hasAcceptance = $acceptance !== null;
    $canManage = auth()->user()?->can('platform.documents.manage');
@endphp

<x-admin-layout :title="'Empresa: '.$company->displayName()">
    @include('modules.admin.companies.partials.nav-slots', [
        'company' => $company,
        'companyNavActive' => 'expediente',
    ])

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 flex-1 min-h-0">
        <section class="rounded-lg border border-slate-800 bg-slate-900/80 p-4 flex flex-col min-h-0">
            <h3 class="text-sm font-semibold text-white shrink-0">Documentos legales</h3>
            <p class="text-xs text-slate-500 mt-0.5 shrink-0">Contratos, normativa y corpus — sin facturas.</p>
            <div class="mt-3 flex-1 min-h-0 overflow-y-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="pb-2 text-left font-medium">Tipo</th>
                            <th class="pb-2 text-left font-medium">Título</th>
                            <th class="pb-2 text-left font-medium">Referencia</th>
                            <th class="pb-2 text-left font-medium">Emitido</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @forelse ($legalDocuments as $doc)
                            <tr>
                                <td class="py-2 text-slate-400">{{ $doc->type->label() }}</td>
                                <td class="py-2 text-slate-200">
                                    {{ $doc->title }}
                                    @if ($doc->is_demo)
                                        <span class="ml-1 text-[10px] uppercase text-amber-400">demo</span>
                                    @endif
                                </td>
                                <td class="py-2 text-slate-500 font-mono text-xs">{{ $doc->reference_number ?? '—' }}</td>
                                <td class="py-2 text-slate-500 text-xs">{{ $doc->issued_at?->format('d/m/Y') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 text-center text-slate-500">Sin documentos legales aún.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-lg border border-slate-800 bg-slate-900/80 p-4 flex flex-col min-h-0 overflow-y-auto">
            <h3 class="text-sm font-semibold text-white">Aceptación contractual (clickwrap)</h3>
            @if ($hasAcceptance)
                <div class="mt-3 rounded-lg border border-emerald-800/50 bg-emerald-900/20 p-3 text-sm">
                    <p class="text-emerald-200 font-medium">Aceptación registrada</p>
                    <p class="text-xs text-emerald-300/80 mt-1">
                        {{ $acceptance->representative_name }} · {{ $acceptance->representative_role }}
                    </p>
                    <p class="text-xs text-emerald-300/70 mt-0.5">
                        {{ $acceptance->representative_document_type }} {{ $acceptance->representative_document_number }}
                    </p>
                    <p class="text-[10px] text-emerald-400/60 mt-2">
                        {{ $acceptance->accepted_at->format('d/m/Y H:i') }} · hash {{ Str::limit($acceptance->content_hash, 16, '') }}
                    </p>
                    <p class="text-[10px] text-emerald-400/50 mt-1">Corpus congelado (inmutable ante cambios de Normoteca).</p>
                </div>

                @if (! empty($frozenCorpus))
                    <details class="mt-3 text-xs text-slate-500">
                        <summary class="cursor-pointer hover:text-slate-300">Ver texto aceptado ({{ count($frozenCorpus) }} docs)</summary>
                        <ul class="mt-2 space-y-2">
                            @foreach ($frozenCorpus as $item)
                                <li class="rounded border border-slate-800 p-2">
                                    <p class="text-slate-300 font-medium">
                                        {{ $item['title'] ?? 'Documento' }}
                                        <span class="text-slate-500 font-normal">(v{{ $item['version'] ?? '?' }})</span>
                                    </p>
                                    @if (! empty($item['content']))
                                        <div class="text-slate-500 mt-1 max-h-32 overflow-y-auto whitespace-pre-line">{{ $item['content'] }}</div>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </details>
                @endif
            @elseif ($canManage)
                <p class="text-xs text-slate-500 mt-1">Representante legal y aceptación de corpus vigente antes del pago.</p>

                @if ($corpus->isNotEmpty())
                    <details class="mt-3 text-xs text-slate-500">
                        <summary class="cursor-pointer hover:text-slate-300">Corpus a aceptar ({{ $corpus->count() }} documentos)</summary>
                        <ul class="mt-2 space-y-2">
                            @foreach ($corpus as $item)
                                <li class="rounded border border-slate-800 p-2">
                                    <p class="text-slate-300 font-medium">{{ $item->title }} (v{{ $item->version }})</p>
                                    <p class="text-slate-500 mt-1 line-clamp-3">{{ $item->content }}</p>
                                </li>
                            @endforeach
                        </ul>
                    </details>
                @endif

                <form method="POST" action="{{ route('admin.documents.expedientes.acceptance', $company) }}" class="mt-4 space-y-3">
                    @csrf
                    <div>
                        <x-ui.label for="representative_name">Nombre representante</x-ui.label>
                        <x-ui.input id="representative_name" name="representative_name" accent="platform" value="{{ old('representative_name') }}" required />
                        <x-ui.field-error name="representative_name" />
                    </div>
                    <div>
                        <x-ui.label for="representative_role">Cargo</x-ui.label>
                        <x-ui.input id="representative_role" name="representative_role" accent="platform" value="{{ old('representative_role') }}" required />
                        <x-ui.field-error name="representative_role" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <x-ui.label for="representative_document_type">Tipo documento</x-ui.label>
                            <x-ui.input id="representative_document_type" name="representative_document_type" accent="platform" placeholder="CC / NIT" value="{{ old('representative_document_type') }}" required />
                            <x-ui.field-error name="representative_document_type" />
                        </div>
                        <div>
                            <x-ui.label for="representative_document_number">Número</x-ui.label>
                            <x-ui.input id="representative_document_number" name="representative_document_number" accent="platform" value="{{ old('representative_document_number') }}" required />
                            <x-ui.field-error name="representative_document_number" />
                        </div>
                    </div>
                    <div class="space-y-2 pt-2">
                        <label class="flex items-start gap-2 text-xs text-slate-400">
                            <input type="checkbox" name="accept_contract" value="1" class="mt-0.5 rounded border-slate-600 bg-slate-950" required />
                            <span>Acepto el contrato de licencia SaaS vigente.</span>
                        </label>
                        <label class="flex items-start gap-2 text-xs text-slate-400">
                            <input type="checkbox" name="accept_terms" value="1" class="mt-0.5 rounded border-slate-600 bg-slate-950" required />
                            <span>Acepto los términos y condiciones de uso.</span>
                        </label>
                        <label class="flex items-start gap-2 text-xs text-slate-400">
                            <input type="checkbox" name="accept_privacy" value="1" class="mt-0.5 rounded border-slate-600 bg-slate-950" required />
                            <span>Acepto la política de tratamiento de datos.</span>
                        </label>
                    </div>
                    <x-ui.button type="submit" variant="platform" size="md" class="w-full mt-2">Registrar aceptación</x-ui.button>
                </form>
            @else
                <p class="mt-3 text-sm text-slate-500">Sin aceptación registrada.</p>
            @endif

            <p class="mt-4 text-xs text-slate-600">
                Pagos y facturas viven en
                <a href="{{ route('admin.companies.historial', $company) }}" class="text-violet-400 hover:text-violet-300">Historial →</a>
            </p>
        </section>
    </div>
</x-admin-layout>
