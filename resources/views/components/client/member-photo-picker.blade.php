@props(['previewUrl' => null])

<div class="flex flex-col items-center" x-data="{ preview: @js($previewUrl) }">
    <label class="group relative inline-flex h-28 w-28 cursor-pointer" title="Seleccionar foto">
        <span class="flex h-full w-full items-center justify-center overflow-hidden rounded-full border-2 border-slate-600 bg-slate-800 group-hover:border-teal-500">
            <img
                x-show="preview"
                x-cloak
                :src="preview"
                alt=""
                class="h-full w-full object-cover"
            >
            <svg x-show="!preview" class="h-10 w-10 text-slate-400 group-hover:text-teal-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path d="M4 8h3l2-2h6l2 2h3v11H4V8Z"/><circle cx="12" cy="13" r="3.2"/>
            </svg>
        </span>
        <span class="absolute -bottom-0.5 -right-0.5 flex h-8 w-8 items-center justify-center rounded-full bg-teal-600 text-white shadow ring-2 ring-slate-900">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path d="M4 8h3l2-2h6l2 2h3v11H4V8Z"/><circle cx="12" cy="13" r="3.2"/>
            </svg>
        </span>
        <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="sr-only"
               @change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : preview">
    </label>
    <p class="mt-2 text-[11px] text-slate-500">JPG, PNG o WebP · máx. 2 MB</p>
    <x-ui.field-error :messages="$errors->get('photo')" />
</div>
