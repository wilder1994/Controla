@php
    use App\Enums\ManualPaymentIntent;

    $fmt = fn (?float $n) => $n !== null ? '$'.number_format($n, 0, ',', '.') : '—';
    $isUpToDate = $isUpToDate ?? $company->isUpToDate();
    $defaultIntent = $isUpToDate
        ? ManualPaymentIntent::Anticipate->value
        : ($company->archived_at || $company->subscription_status?->value === 'suspended'
            ? ManualPaymentIntent::Reactivate->value
            : ManualPaymentIntent::Renew->value);
@endphp

@can('platform.companies.manage')
    <div
        x-show="payOpen"
        x-cloak
        @keydown.escape.window="payOpen = false"
        class="fixed inset-0 z-50 overflow-y-auto"
        style="display: none;"
    >
        <div class="flex items-center justify-center min-h-screen px-4 py-8 text-center">
            <div
                x-show="payOpen"
                x-transition.opacity
                @click="payOpen = false"
                class="fixed inset-0 bg-black/60 backdrop-blur-sm"
            ></div>

            <div
                x-show="payOpen"
                x-transition
                @click.stop
                class="relative w-full max-w-lg rounded-xl border border-slate-700 bg-slate-900 text-left shadow-2xl"
            >
                <div class="flex items-start justify-between gap-3 border-b border-slate-800 px-5 py-4">
                    <div>
                        <h3 class="text-sm font-semibold text-white">Pagar factura</h3>
                        <p class="text-xs text-slate-500 mt-1">Consignación manual · referencia y soporte obligatorios</p>
                    </div>
                    <button type="button" @click="payOpen = false" class="text-slate-500 hover:text-white text-sm">✕</button>
                </div>

                <div class="px-5 py-4 space-y-4">
                    <div class="rounded-lg border border-slate-800 bg-slate-950/50 p-3 text-sm space-y-2">
                        <div class="flex justify-between gap-3">
                            <span class="text-slate-500">Empresa</span>
                            <span class="text-slate-200 text-right">{{ $company->displayName() }}</span>
                        </div>
                        <div class="flex justify-between gap-3">
                            <span class="text-slate-500">Monto</span>
                            <span class="text-white font-semibold tabular-nums">{{ $fmt($company->contractedAmount()) }}</span>
                        </div>
                        @if ($company->package_ends_at)
                            <div class="flex justify-between gap-3">
                                <span class="text-slate-500">Vigencia actual</span>
                                <span class="text-slate-300">hasta {{ $company->package_ends_at->format('d/m/Y') }}</span>
                            </div>
                        @endif
                    </div>

                    @if ($isUpToDate)
                        <p class="text-sm text-emerald-200/90 rounded-lg border border-emerald-800/50 bg-emerald-900/20 px-3 py-2">
                            La cuenta está al día. No necesita pagar el periodo actual.
                            Puede anticipar el próximo {{ $company->billing_cycle?->value === 'annual' ? 'año' : 'mes' }}.
                        </p>
                    @endif

                    @if (! $hasAcceptance)
                        <p class="text-sm text-amber-300/90 rounded-lg border border-amber-800/50 bg-amber-900/20 px-3 py-2">
                            Completa la aceptación contractual en Expediente docs antes de registrar el pago.
                        </p>
                        <div class="flex justify-end gap-2">
                            <x-ui.button type="button" variant="secondary" size="sm" @click="payOpen = false">Cerrar</x-ui.button>
                            <x-ui.button variant="platform" size="sm" :href="route('admin.documents.expedientes.show', $company)">
                                Ir a expediente
                            </x-ui.button>
                        </div>
                    @else
                        <form
                            method="POST"
                            action="{{ route('admin.companies.payment.manual', $company) }}"
                            enctype="multipart/form-data"
                            class="space-y-3"
                        >
                            @csrf
                            <input type="hidden" name="action_context" value="pay" />
                            <div>
                                <x-ui.label for="pay_intent">Tipo de pago</x-ui.label>
                                <select
                                    id="pay_intent"
                                    name="intent"
                                    required
                                    class="w-full h-9 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white"
                                >
                                    @if ($isUpToDate)
                                        <option value="{{ ManualPaymentIntent::Anticipate->value }}" @selected(old('intent', $defaultIntent) === ManualPaymentIntent::Anticipate->value)>
                                            Anticipar próximo periodo
                                        </option>
                                    @else
                                        <option value="{{ ManualPaymentIntent::Renew->value }}" @selected(old('intent', $defaultIntent) === ManualPaymentIntent::Renew->value)>
                                            Renovar / poner al día
                                        </option>
                                        <option value="{{ ManualPaymentIntent::Reactivate->value }}" @selected(old('intent', $defaultIntent) === ManualPaymentIntent::Reactivate->value)>
                                            Reactivar membresía
                                        </option>
                                    @endif
                                </select>
                                <x-ui.field-error name="intent" />
                            </div>
                            <div>
                                <x-ui.label for="pay_reference">Referencia de consignación</x-ui.label>
                                <x-ui.input
                                    id="pay_reference"
                                    name="reference"
                                    accent="platform"
                                    placeholder="TRX-88421"
                                    value="{{ old('reference') }}"
                                    required
                                />
                                <x-ui.field-error name="reference" />
                            </div>
                            <div>
                                <x-ui.label for="pay_proof">Soporte de pago (PDF o imagen)</x-ui.label>
                                <input
                                    id="pay_proof"
                                    type="file"
                                    name="proof"
                                    accept=".pdf,.jpg,.jpeg,.png,.webp"
                                    required
                                    class="block w-full text-xs text-slate-400 file:mr-3 file:rounded-lg file:border-0 file:bg-violet-600 file:px-3 file:py-2 file:text-xs file:font-medium file:text-white"
                                />
                                <x-ui.field-error name="proof" />
                            </div>
                            <div class="flex justify-end gap-2 pt-1">
                                <x-ui.button type="button" variant="secondary" size="sm" @click="payOpen = false">
                                    Cancelar
                                </x-ui.button>
                                <x-ui.button type="submit" variant="platform" size="sm">
                                    Confirmar pago
                                </x-ui.button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endcan
