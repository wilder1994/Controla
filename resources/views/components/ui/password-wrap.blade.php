@props(['disabled' => false])

<div class="relative" x-data="{ show: false }">
    <input
        @disabled($disabled)
        x-bind:type="show ? 'text' : 'password'"
        {{ $attributes }}
    >
    <button
        type="button"
        class="absolute inset-y-0 right-0 z-10 flex w-10 items-center justify-center rounded-r-lg border-0 bg-transparent p-0 text-slate-400 hover:text-cyan-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400/40"
        @click="show = !show"
        :aria-pressed="show.toString()"
        :aria-label="show ? 'Ocultar contraseña' : 'Mostrar contraseña'"
        tabindex="0"
    >
        <svg x-show="!show" x-cloak class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12s-3.75 6.75-9.75 6.75S2.25 12 2.25 12Z" />
            <circle cx="12" cy="12" r="2.75" />
        </svg>
        <svg x-show="show" x-cloak class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M9.9 9.9A3 3 0 0012 15a3 3 0 002.1-.9M6.6 6.6C4.4 8 2.75 12 2.75 12s3.75 6.75 9.75 6.75c1.7 0 3.23-.4 4.55-1.05M17.4 17.4C19.6 16 21.25 12 21.25 12s-3.75-6.75-9.75-6.75c-.86 0-1.68.1-2.45.3" />
        </svg>
    </button>
</div>
