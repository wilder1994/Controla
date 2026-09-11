@php
    use App\Enums\PartyType;
@endphp

@props([
    'company',
    'accent' => 'default',
    'formAction',
    'cancelUrl',
    'logoPreviewUrl' => null,
])

<form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" class="space-y-4 rounded-lg border border-slate-800 bg-slate-900/80 p-4">
    @csrf
    @method('PUT')

    <div>
        <x-ui.label for="party_type">Tipo de suscriptor</x-ui.label>
        <select
            name="party_type"
            id="party_type"
            class="w-full h-9 px-3 text-sm rounded-lg border border-slate-700 bg-slate-950 text-white focus:ring-1 {{ $accent === 'platform' ? 'focus:border-violet-500 focus:ring-violet-500/30' : 'focus:border-indigo-500 focus:ring-indigo-500/30' }}"
        >
            @foreach (PartyType::cases() as $type)
                <option value="{{ $type->value }}" @selected(old('party_type', $company->party_type?->value) === $type->value)>
                    {{ $type->label() }}
                </option>
            @endforeach
        </select>
        <x-ui.field-error :messages="$errors->get('party_type')" />
    </div>

    <div>
        <x-ui.label for="legal_name">Razón social / nombre legal</x-ui.label>
        <x-ui.input id="legal_name" name="legal_name" :value="old('legal_name', $company->legal_name)" required :accent="$accent" />
        <x-ui.field-error :messages="$errors->get('legal_name')" />
    </div>

    <div>
        <x-ui.label for="trade_name">Nombre comercial</x-ui.label>
        <x-ui.input id="trade_name" name="trade_name" :value="old('trade_name', $company->trade_name)" :accent="$accent" />
        <x-ui.field-error :messages="$errors->get('trade_name')" />
    </div>

  @if ($company->hasCompletedAcceptance())
        <div>
            <x-ui.label>NIT / identificador fiscal</x-ui.label>
            <p class="text-sm text-slate-300">{{ $company->tax_id }}</p>
            <p class="text-xs text-slate-500 mt-1">No editable tras aceptación contractual.</p>
        </div>
    @else
        <div>
            <x-ui.label for="tax_id">NIT / identificador fiscal</x-ui.label>
            <x-ui.input id="tax_id" name="tax_id" :value="old('tax_id', $company->tax_id)" required :accent="$accent" />
            <x-ui.field-error :messages="$errors->get('tax_id')" />
        </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-ui.label for="email">Email comercial</x-ui.label>
            <x-ui.input type="email" id="email" name="email" :value="old('email', $company->email)" :accent="$accent" />
            <x-ui.field-error :messages="$errors->get('email')" />
        </div>
        <div>
            <x-ui.label for="phone">Teléfono</x-ui.label>
            <x-ui.input id="phone" name="phone" :value="old('phone', $company->phone)" :accent="$accent" />
            <x-ui.field-error :messages="$errors->get('phone')" />
        </div>
    </div>

    <x-ui.geo-address-fields
        :address="old('address', $company->address)"
        :city="old('city', $company->city)"
        :department="old('department', $company->department)"
        :latitude="old('latitude', $company->latitude)"
        :longitude="old('longitude', $company->longitude)"
        :accent="$accent"
    />

    <div class="rounded-lg border border-slate-800 bg-slate-950/60 p-3 space-y-3">
        <p class="text-sm font-medium text-slate-200">Marca e informes de campo</p>
        <p class="text-xs text-slate-500">Logo y texto de cabecera de las fichas de revista. No aparece la marca de la plataforma.</p>

        <div
            x-data='companyLogoField({{ \Illuminate\Support\Js::from($logoPreviewUrl) }})'
            @paste.window="onPaste($event)"
        >
            <x-ui.label>Logo</x-ui.label>
            <input x-ref="filePicker" type="file" accept="image/png,image/jpeg,image/webp" class="hidden" @change="onPicker($event)">
            <input x-ref="logoInput" type="file" name="logo" class="hidden" accept="image/png,image/jpeg,image/webp">
            <input type="hidden" name="remove_logo" :value="removeLogo ? '1' : '0'">

            <div
                tabindex="0"
                role="button"
                @click="pick()"
                @keydown.enter.prevent="pick()"
                @dragover.prevent="dragging = true"
                @dragleave.prevent="dragging = false"
                @drop.prevent="onDrop($event)"
                :class="dragging
                    ? '{{ $accent === 'platform' ? 'border-violet-400 bg-violet-950/40' : 'border-indigo-400 bg-indigo-950/40' }}'
                    : 'border-slate-600 bg-slate-950/80 hover:border-slate-500'"
                class="mt-2 rounded-xl border-2 border-dashed px-4 py-5 text-center outline-none focus:ring-1 {{ $accent === 'platform' ? 'focus:ring-violet-500/40' : 'focus:ring-indigo-500/40' }}"
            >
                <template x-if="preview">
                    <div class="flex flex-col items-center gap-3" @click.stop>
                        <img :src="preview" alt="Logo" class="h-20 w-20 rounded-lg object-contain bg-white p-1">
                        <div class="flex flex-wrap items-center justify-center gap-2">
                            <x-ui.button type="button" size="sm" @click="pick()">Cambiar</x-ui.button>
                            <x-ui.button type="button" size="sm" variant="secondary" @click="clear()">Quitar</x-ui.button>
                        </div>
                    </div>
                </template>
                <template x-if="!preview">
                    <div>
                        <p class="text-sm text-slate-200">Selecciona, pega o arrastra el logo</p>
                        <p class="mt-1 text-xs text-slate-500">PNG, JPG o WebP · máximo 2 MB</p>
                    </div>
                </template>
            </div>
            <p x-show="error" x-text="error" class="mt-2 text-xs text-rose-400" x-cloak></p>
            <x-ui.field-error :messages="$errors->get('logo')" />

            <div
                x-show="editorOpen"
                x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4"
                @keydown.escape.window="closeEditor()"
            >
                <div class="w-full max-w-md rounded-xl border border-slate-700 bg-slate-900 p-4 shadow-xl" @click.stop>
                    <p class="text-sm font-semibold text-white">Ajustar logo</p>
                    <p class="mt-1 text-xs text-slate-500">Gira y arrastra para encuadrar en cuadrado.</p>
                    <div class="mt-3 aspect-square overflow-hidden rounded-lg border border-slate-700 bg-slate-950">
                        <canvas
                            x-ref="cropCanvas"
                            class="h-full w-full cursor-move touch-none"
                            @pointerdown="pointerStart($event)"
                            @pointermove="pointerMove($event)"
                            @pointerup="pointerEnd()"
                            @pointerleave="pointerEnd()"
                            @touchstart.prevent="pointerStart($event)"
                            @touchmove.prevent="pointerMove($event)"
                            @touchend="pointerEnd()"
                        ></canvas>
                    </div>
                    <div class="mt-4 flex flex-wrap items-center justify-end gap-2">
                        <x-ui.button type="button" variant="secondary" @click="rotate()">Girar</x-ui.button>
                        <x-ui.button type="button" variant="secondary" @click="closeEditor()">Cancelar</x-ui.button>
                        <x-ui.button type="button" :variant="$accent === 'platform' ? 'platform' : 'primary'" @click="accept()">Aceptar</x-ui.button>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <x-ui.label for="field_sheet_intro">Encabezado de la revista</x-ui.label>
            <textarea
                id="field_sheet_intro"
                name="field_sheet_intro"
                rows="6"
                class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white focus:ring-1 {{ $accent === 'platform' ? 'focus:border-violet-500 focus:ring-violet-500/30' : 'focus:border-indigo-500 focus:ring-indigo-500/30' }}"
            >{{ old('field_sheet_intro', $company->field_sheet_intro ?: \App\Support\Supervision\SupervisorFieldSheetIntro::DEFAULT) }}</textarea>
            <p class="text-xs text-slate-500 mt-1">Se copia en cada revista al guardarla. Vacío = texto sugerido (Decreto 356 de 1994).</p>
            <x-ui.field-error :messages="$errors->get('field_sheet_intro')" />
        </div>
    </div>

    <div class="flex items-center gap-3 pt-2">
        <x-ui.button type="submit" :variant="$accent === 'platform' ? 'platform' : 'primary'">Guardar datos</x-ui.button>
        <a href="{{ $cancelUrl }}" class="text-sm text-slate-400 hover:text-white">Cancelar</a>
    </div>
</form>
