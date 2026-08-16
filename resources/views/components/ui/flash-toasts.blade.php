@props([
    'rail' => 'max-w-[1600px] mx-auto w-full px-4 sm:px-6 lg:px-8',
])

@if (session('success') || session('warning') || session('error'))
    <div class="{{ $rail }} pt-3 shrink-0 space-y-2">
        @if (session('success'))
            <div
                x-data="{ show: true }"
                x-init="setTimeout(() => show = false, 4500)"
                x-show="show"
                x-cloak
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="rounded-lg bg-emerald-900/40 border border-emerald-700 text-emerald-200 px-4 py-3 text-sm"
                role="status"
            >
                {{ session('success') }}
            </div>
        @endif
        @if (session('warning'))
            <div
                x-data="{ show: true }"
                x-init="setTimeout(() => show = false, 5500)"
                x-show="show"
                x-cloak
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="rounded-lg bg-amber-900/40 border border-amber-700 text-amber-200 px-4 py-3 text-sm"
                role="status"
            >
                {{ session('warning') }}
            </div>
        @endif
        @if (session('error'))
            <div
                x-data="{ show: true }"
                x-init="setTimeout(() => show = false, 6000)"
                x-show="show"
                x-cloak
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="rounded-lg bg-red-900/40 border border-red-700 text-red-200 px-4 py-3 text-sm"
                role="status"
            >
                {{ session('error') }}
            </div>
        @endif
    </div>
@endif
