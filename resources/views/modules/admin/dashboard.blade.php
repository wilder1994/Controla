<x-admin-layout title="Resumen plataforma">
    <div class="space-y-8">
        <div>
            <h2 class="text-2xl font-bold text-white">Panel de plataforma</h2>
            <p class="text-sm text-slate-400 mt-1">Vista global — empresas de seguridad, clientes y usuarios del SaaS.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="rounded-xl bg-slate-900 border border-slate-800 p-5">
                <p class="text-xs uppercase tracking-wide text-slate-500">Empresas</p>
                <p class="mt-2 text-3xl font-bold text-white">{{ $metrics['companies_total'] }}</p>
            </div>
            <div class="rounded-xl bg-slate-900 border border-slate-800 p-5">
                <p class="text-xs uppercase tracking-wide text-slate-500">Empresas activas</p>
                <p class="mt-2 text-3xl font-bold text-violet-400">{{ $metrics['companies_active'] }}</p>
            </div>
            <div class="rounded-xl bg-slate-900 border border-slate-800 p-5">
                <p class="text-xs uppercase tracking-wide text-slate-500">Clientes</p>
                <p class="mt-2 text-3xl font-bold text-white">{{ $metrics['clients_total'] }}</p>
            </div>
            <div class="rounded-xl bg-slate-900 border border-slate-800 p-5">
                <p class="text-xs uppercase tracking-wide text-slate-500">Clientes activos</p>
                <p class="mt-2 text-3xl font-bold text-emerald-400">{{ $metrics['clients_active'] }}</p>
            </div>
            <div class="rounded-xl bg-slate-900 border border-slate-800 p-5">
                <p class="text-xs uppercase tracking-wide text-slate-500">Usuarios</p>
                <p class="mt-2 text-3xl font-bold text-white">{{ $metrics['users_total'] }}</p>
            </div>
        </div>

        <div class="rounded-xl bg-slate-900 border border-slate-800 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-800">
                <h3 class="font-semibold text-white">Empresas de seguridad</h3>
            </div>
            <ul class="divide-y divide-slate-800">
                @forelse ($recentCompanies as $company)
                    <li class="px-5 py-4 flex items-center justify-between gap-4">
                        <div>
                            <p class="font-medium text-white">{{ $company->trade_name ?? $company->legal_name }}</p>
                            <p class="text-xs text-slate-500">{{ $company->tax_id }} · {{ $company->clients_count }} clientes</p>
                        </div>
                        @if ($company->is_active)
                            <span class="text-xs text-emerald-400">Activa</span>
                        @else
                            <span class="text-xs text-slate-500">Inactiva</span>
                        @endif
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-slate-500 text-sm">No hay empresas registradas.</li>
                @endforelse
            </ul>
        </div>
    </div>
</x-admin-layout>
