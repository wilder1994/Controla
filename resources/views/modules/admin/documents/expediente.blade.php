@php
    use App\Enums\PartyType;
    use Illuminate\Support\Str;
    $fmt = fn (?float $n) => $n !== null ? '$'.number_format($n, 0, ',', '.') : '—';
    $hasAcceptance = $acceptance !== null;
    $canManage = auth()->user()?->can('platform.documents.manage');
@endphp

<x-admin-layout :title="'Expediente · '.$company->displayName()">
    <div class="flex flex-col flex-1 min-h-0 gap-4">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 shrink-0">
            <div>
                <p class="text-xs text-slate-500">Expediente comercial</p>
                <h3 class="text-sm font-semibold text-white mt-0.5">{{ $company->displayName() }}</h3>
                <p class="text-xs text-slate-500 mt-1">
                    {{ $company->tax_id }}
                    · {{ $company->party_type?->label() ?? PartyType::LegalEntity->label() }}
                </p>
            </div>
            <div class="flex gap-2">
                <x-ui.button variant="secondary" :href="route('admin.documents.expedientes')" size="sm">← Expedientes</x-ui.button>
                <x-ui.button variant="secondary" :href="route('admin.companies.show', $company)" size="sm">Empresa</x-ui.button>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-4 flex-1 min-h-0">
            {{-- Timeline --}}
            <section class="xl:col-span-5 rounded-lg border border-slate-800 bg-slate-900/80 p-4 flex flex-col min-h-0">
                <h3 class="text-sm font-semibold text-white shrink-0">Línea de tiempo</h3>
                <p class="text-xs text-slate-500 mt-0.5 shrink-0">Evidencias automáticas del ciclo comercial y operativo.</p>
                <div class="mt-3 flex-1 min-h-0 overflow-y-auto space-y-2">
                    @forelse ($timeline as $event)
                        <div class="rounded-lg border border-slate-800 bg-slate-950/50 px-3 py-2">
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-sm font-medium text-slate-200">{{ $event->event_type->label() }}</p>
                                <time class="text-[10px] text-slate-500 shrink-0">{{ $event->occurred_at->format('d/m/Y H:i') }}</time>
                            </div>
                            @if ($event->title)
                                <p class="text-xs text-slate-400 mt-1">{{ $event->title }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Sin eventos registrados.</p>
                    @endforelse
                </div>
            </section>

            {{-- Documentos --}}
            <section class="xl:col-span-7 rounded-lg border border-slate-800 bg-slate-900/80 p-4 flex flex-col min-h-0">
                <h3 class="text-sm font-semibold text-white shrink-0">Documentos del expediente</h3>
                <div class="mt-3 flex-1 min-h-0 overflow-y-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="pb-2 text-left font-medium">Tipo</th>
                                <th class="pb-2 text-left font-medium">Título</th>
                                <th class="pb-2 text-left font-medium">Referencia</th>
                                <th class="pb-2 text-left font-medium">Importe</th>
                                <th class="pb-2 text-left font-medium">Emitido</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800">
                            @forelse ($documents as $doc)
                                <tr>
                                    <td class="py-2 text-slate-400">{{ $doc->type->label() }}</td>
                                    <td class="py-2 text-slate-200">
                                        {{ $doc->title }}
                                        @if ($doc->is_demo)
                                            <span class="ml-1 text-[10px] uppercase text-amber-400">demo</span>
                                        @endif
                                    </td>
                                    <td class="py-2 text-slate-500 font-mono text-xs">{{ $doc->reference_number ?? '—' }}</td>
                                    <td class="py-2 text-slate-300 tabular-nums">{{ $fmt($doc->amount) }}</td>
                                    <td class="py-2 text-slate-500 text-xs">{{ $doc->issued_at?->format('d/m/Y') ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-6 text-center text-slate-500">Sin documentos en expediente.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($payments->isNotEmpty())
                    <div class="mt-4 pt-4 border-t border-slate-800 shrink-0">
                        <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-400">Pagos registrados</h4>
                        <ul class="mt-2 space-y-1 text-xs text-slate-400">
                            @foreach ($payments as $payment)
                                <li>
                                    {{ $payment->paid_at?->format('d/m/Y H:i') }}
                                    — {{ $fmt((float) $payment->amount) }}
                                    — {{ $payment->method->label() }}
                                    @if ($payment->reference)
                                        <span class="text-slate-600">({{ $payment->reference }})</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </section>
        </div>

        @if ($canManage)
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 shrink-0">
                {{-- Aceptación --}}
                <section class="rounded-lg border border-slate-800 bg-slate-900/80 p-4">
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
                        </div>
                    @else
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
                    @endif
                </section>

                {{-- Pago manual --}}
                <section class="rounded-lg border border-slate-800 bg-slate-900/80 p-4">
                    <h3 class="text-sm font-semibold text-white">Pago manual</h3>
                    <p class="text-xs text-slate-500 mt-1">
                        Registra cobro fuera de pasarela. Requiere aceptación previa. Genera factura demo en modo {{ config('billing.mode') }}.
                    </p>
                    @if (! $hasAcceptance)
                        <p class="mt-3 text-sm text-amber-300/90 rounded-lg border border-amber-800/50 bg-amber-900/20 px-3 py-2">
                            Completa la aceptación contractual antes de registrar el pago.
                        </p>
                    @else
                        <form method="POST" action="{{ route('admin.documents.expedientes.payment.manual', $company) }}" class="mt-4 space-y-3">
                            @csrf
                            <div>
                                <x-ui.label for="reference">Referencia (opcional)</x-ui.label>
                                <x-ui.input id="reference" name="reference" accent="platform" placeholder="Transferencia, recibo, etc." value="{{ old('reference') }}" />
                                <x-ui.field-error name="reference" />
                            </div>
                            <p class="text-xs text-slate-500">
                                Monto contratado: {{ $fmt($company->contractedAmount()) }}
                                @if ($company->billing_cycle)
                                    · ciclo {{ $company->billing_cycle->label() }}
                                @endif
                            </p>
                            <x-ui.button type="submit" variant="platform" size="md" class="w-full">Registrar pago y factura demo</x-ui.button>
                        </form>

                        <form method="POST" action="{{ route('admin.documents.expedientes.payment.local-checkout', $company) }}" class="mt-3">
                            @csrf
                            <x-ui.button type="submit" variant="secondary" size="md" class="w-full">
                                Simular pago online (checkout local)
                            </x-ui.button>
                        </form>
                    @endif
                </section>
            </div>
        @endif
    </div>
</x-admin-layout>
