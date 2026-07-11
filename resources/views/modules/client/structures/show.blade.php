<x-client-layout :title="$structure->name">
    <div class="space-y-6" x-data="{ tab: 'datos' }">
        <div>
            <a href="{{ route('client.structures.index') }}" class="text-sm text-teal-400 hover:text-teal-300">← Volver al árbol</a>
            <h2 class="text-2xl font-bold text-white mt-2">{{ $structure->name }}</h2>
            <p class="text-sm text-slate-400">{{ $structure->full_path }} · {{ $structure->type->label() }}</p>
        </div>

        <div class="border-b border-slate-800">
            <nav class="flex gap-4 text-sm">
                <button type="button" @click="tab = 'datos'" :class="tab === 'datos' ? 'border-teal-500 text-teal-300' : 'border-transparent text-slate-500'" class="px-1 py-2 border-b-2 font-medium">Datos</button>
                <button type="button" @click="tab = 'visitas'" :class="tab === 'visitas' ? 'border-teal-500 text-teal-300' : 'border-transparent text-slate-500'" class="px-1 py-2 border-b-2 font-medium">Visitas</button>
                <button type="button" @click="tab = 'correspondencia'" :class="tab === 'correspondencia' ? 'border-teal-500 text-teal-300' : 'border-transparent text-slate-500'" class="px-1 py-2 border-b-2 font-medium">Correspondencia</button>
            </nav>
        </div>

        <div x-show="tab === 'datos'" x-cloak>
            <div class="grid md:grid-cols-3 gap-4 mb-6">
                <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
                    <p class="text-xs uppercase text-slate-500">Personas</p>
                    <p class="text-2xl font-bold text-white">{{ $structure->members->count() }}</p>
                </div>
                <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
                    <p class="text-xs uppercase text-slate-500">Vehículos</p>
                    <p class="text-2xl font-bold text-white">{{ $structure->vehicles->count() }}</p>
                </div>
                <div class="rounded-xl border border-slate-800 bg-slate-900 p-4">
                    <p class="text-xs uppercase text-slate-500">Mascotas</p>
                    <p class="text-2xl font-bold text-white">{{ $structure->pets->count() }}</p>
                </div>
            </div>

            <div class="rounded-xl border border-slate-800 overflow-hidden">
                <div class="px-4 py-3 bg-slate-950/60 border-b border-slate-800">
                    <h3 class="text-sm font-semibold text-white">Personas en esta unidad</h3>
                </div>
                <table class="min-w-full text-sm">
                    <tbody class="divide-y divide-slate-800">
                        @forelse ($structure->members as $member)
                            <tr>
                                <td class="px-4 py-3">
                                    <a href="{{ route('client.members.show', $member) }}" class="text-teal-300 hover:text-teal-200">{{ $member->full_name }}</a>
                                </td>
                                <td class="px-4 py-3 text-slate-400">{{ $member->member_type->label() }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $member->document_number }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-6 text-slate-500">Sin personas registradas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div x-show="tab === 'visitas'" x-cloak>
            <div class="rounded-xl border border-slate-800 overflow-hidden">
                <div class="px-4 py-3 bg-slate-950/60 border-b border-slate-800 flex justify-between items-center">
                    <h3 class="text-sm font-semibold text-white">Autorizaciones de visita</h3>
                    <a href="{{ route('client.authorizations.create') }}" class="text-xs text-teal-400 hover:text-teal-300">+ Nueva</a>
                </div>
                <table class="min-w-full text-sm">
                    <thead class="text-xs uppercase text-slate-500 bg-slate-950/40">
                        <tr>
                            <th class="px-4 py-2 text-left">Visitante</th>
                            <th class="px-4 py-2 text-left">Fecha</th>
                            <th class="px-4 py-2 text-left">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @forelse ($authorizations as $auth)
                            <tr>
                                <td class="px-4 py-3 text-white">{{ $auth->visitor_name }}</td>
                                <td class="px-4 py-3 text-slate-400">{{ $auth->valid_for_date?->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-slate-400">{{ $auth->status->label() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-6 text-slate-500">Sin autorizaciones para esta unidad.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div x-show="tab === 'correspondencia'" x-cloak>
            <div class="rounded-xl border border-slate-800 bg-slate-900 p-6">
                <h3 class="text-sm font-semibold text-white mb-2">Correspondencia de la unidad</h3>
                <p class="text-sm text-slate-400 mb-4">El registro operativo de paquetes se gestiona en portería. Aquí verás el resumen cuando la unidad reciba correspondencia vinculada al censo (Fase 2).</p>
                <a href="{{ route('access.correspondence.index') }}" class="inline-flex text-sm text-teal-400 hover:text-teal-300">Ir a correspondencia en portería →</a>
            </div>
        </div>
    </div>
</x-client-layout>
