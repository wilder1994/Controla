@extends('layouts.public')

@section('content')
    @include('modules.public.signup._steps', ['step' => 2])

    <div class="max-w-xl space-y-4">
        <p class="text-sm text-slate-400">
            Representante legal y aceptación del corpus vigente
            @if ($intent->package_sku)
                para el plan <span class="text-slate-200">{{ $intent->package_sku->label() }}</span>.
            @endif
        </p>

        @if ($corpus->isNotEmpty())
            <div class="space-y-2">
                @foreach ($corpus as $doc)
                    <details class="rounded-lg border border-slate-800 bg-slate-900/60 p-3 text-xs text-slate-400">
                        <summary class="cursor-pointer text-slate-300 font-medium">
                            {{ $doc->title }}
                            <span class="text-slate-500 font-normal">(v{{ $doc->version }})</span>
                        </summary>
                        <div class="mt-2 max-h-48 overflow-y-auto whitespace-pre-line text-slate-400 leading-relaxed border-t border-slate-800 pt-2">
                            {{ $doc->content }}
                        </div>
                    </details>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('signup.legal.store', $intent) }}" class="space-y-4 rounded-xl border border-slate-800 bg-slate-900/80 p-4">
            @csrf
            <div>
                <x-ui.label for="representative_name">Nombre representante</x-ui.label>
                <x-ui.input id="representative_name" name="representative_name" value="{{ old('representative_name', $intent->representative_name) }}" required />
                <x-ui.field-error name="representative_name" />
            </div>
            <div>
                <x-ui.label for="representative_role">Cargo</x-ui.label>
                <x-ui.input id="representative_role" name="representative_role" value="{{ old('representative_role', $intent->representative_role) }}" required />
                <x-ui.field-error name="representative_role" />
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <x-ui.label for="representative_document_type">Tipo documento</x-ui.label>
                    <x-ui.input id="representative_document_type" name="representative_document_type" value="{{ old('representative_document_type', $intent->representative_document_type) }}" required />
                    <x-ui.field-error name="representative_document_type" />
                </div>
                <div>
                    <x-ui.label for="representative_document_number">Número</x-ui.label>
                    <x-ui.input id="representative_document_number" name="representative_document_number" value="{{ old('representative_document_number', $intent->representative_document_number) }}" required />
                    <x-ui.field-error name="representative_document_number" />
                </div>
            </div>
            <div class="space-y-2 pt-2">
                <label class="flex items-start gap-2 text-sm text-slate-400">
                    <input type="checkbox" name="accept_contract" value="1" class="mt-1 rounded border-slate-600" required />
                    <span>Acepto el contrato de licencia SaaS del plan seleccionado</span>
                </label>
                <label class="flex items-start gap-2 text-sm text-slate-400">
                    <input type="checkbox" name="accept_terms" value="1" class="mt-1 rounded border-slate-600" required />
                    <span>Acepto los términos y condiciones</span>
                </label>
                <label class="flex items-start gap-2 text-sm text-slate-400">
                    <input type="checkbox" name="accept_privacy" value="1" class="mt-1 rounded border-slate-600" required />
                    <span>Acepto la política de tratamiento de datos</span>
                </label>
            </div>
            <x-ui.button type="submit" class="w-full">Continuar → Resumen</x-ui.button>
        </form>
    </div>
@endsection
