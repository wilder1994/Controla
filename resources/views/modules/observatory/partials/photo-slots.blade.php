@php
    $hint = $hint ?? 'Toque el recuadro para abrir la cámara. Hasta 3 fotos (opcional).';
@endphp
<div>
    <p class="text-xs text-slate-400 mb-1.5">Fotos</p>
    <div class="grid grid-cols-3 gap-2">
        @for ($i = 0; $i < 3; $i++)
            <label class="relative flex aspect-square cursor-pointer flex-col items-center justify-center overflow-hidden rounded-lg border border-slate-700 bg-slate-950 text-slate-400 hover:border-teal-500/60">
                <input
                    type="file"
                    accept="image/*"
                    capture="environment"
                    class="sr-only"
                    :name="previews[{{ $i }}] ? 'photos[]' : null"
                    @change="setPhoto($event, {{ $i }})"
                >
                <img
                    x-show="previews[{{ $i }}]"
                    x-cloak
                    :src="previews[{{ $i }}]"
                    alt=""
                    class="absolute inset-0 h-full w-full object-cover"
                >
                <span x-show="!previews[{{ $i }}]" class="flex flex-col items-center gap-1 px-1 text-center">
                    <svg class="size-7 text-teal-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z" />
                    </svg>
                    <span class="text-[10px] font-medium leading-tight">Foto {{ $i + 1 }}</span>
                </span>
            </label>
        @endfor
    </div>
    <p class="mt-1.5 text-[11px] text-slate-500">{{ $hint }}</p>
</div>
