<x-client-layout :title="$pet->name">
    <div class="space-y-6 max-w-3xl">
        <div>
            <a href="{{ route('client.pets.index') }}" class="text-sm text-teal-400 hover:text-teal-300">← Mascotas</a>
            <h2 class="text-2xl font-bold text-white mt-2">{{ $pet->name }}</h2>
            <p class="text-sm text-slate-400">
                {{ $pet->structure?->name ?? '—' }}
                @if ($pet->structure?->installation)
                    · {{ $pet->structure->installation->name }}
                @endif
                · {{ $pet->species->label() }}
            </p>
        </div>

        <div class="grid md:grid-cols-2 gap-6">
            <div class="rounded-xl border border-slate-800 bg-slate-900 p-4 space-y-3">
                <h3 class="text-sm font-semibold text-white">Datos de la mascota</h3>
                <p class="text-sm text-slate-300">Especie: <span class="text-white">{{ $pet->species->label() }}</span></p>
                <p class="text-sm text-slate-300">Raza: {{ $pet->breed ?? '—' }}</p>
                <p class="text-sm text-slate-300">
                    Peligroso:
                    @if($pet->is_potentially_dangerous)
                        <span class="text-red-400 font-semibold">Sí</span>
                    @else
                        <span class="text-slate-500">No</span>
                    @endif
                </p>
            </div>
        </div>
    </div>
</x-client-layout>
