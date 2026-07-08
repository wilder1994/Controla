<x-company-layout title="Seleccionar conjunto">
    <div class="max-w-3xl mx-auto">
        <h2 class="text-2xl font-bold text-white">¿En qué conjunto operas?</h2>
        <p class="text-sm text-slate-400 mt-2">Elige el cliente para activar el contexto de portería y censo.</p>

        @if (session('warning'))
            <div class="mt-4 rounded-lg bg-amber-900/40 border border-amber-700 text-amber-200 px-4 py-3 text-sm">{{ session('warning') }}</div>
        @endif

        <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach ($clients as $client)
                <form action="{{ route('company.clients.activate', $client) }}" method="POST"
                      class="rounded-xl border border-slate-800 bg-slate-900 p-5 hover:border-indigo-600 transition">
                    @csrf
                    <h3 class="font-semibold text-white">{{ $client->name }}</h3>
                    <p class="text-xs text-slate-500 mt-1">
                        {{ $client->plan_tier->label() }} · {{ $client->slug }}
                        @if ($client->relationLoaded('securityCompany') && $client->securityCompany)
                            · {{ $client->securityCompany->trade_name }}
                        @endif
                    </p>
                    <button type="submit" class="mt-4 text-sm font-medium text-indigo-400 hover:text-indigo-300">
                        Entrar a portería →
                    </button>
                </form>
            @endforeach
        </div>
    </div>
</x-company-layout>
