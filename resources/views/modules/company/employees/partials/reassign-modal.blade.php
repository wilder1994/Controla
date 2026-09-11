@php
    $currentPost = $currentPost ?? $employee->supervisorPosts->first();
    $tree = $assignmentTree ?? [];
    $formConfig = [
        'tree' => $tree,
        'clientId' => $currentPost?->client_id,
        'installationId' => $currentPost?->installation_id,
        'postId' => $currentPost?->id,
    ];
@endphp

<div
    x-data="employeeReassignForm(@js($formConfig))"
    x-on:open-employee-reassign.window="open = true"
    x-on:keydown.escape.window="if (open) open = false"
>
    <div x-show="open" x-cloak class="fixed inset-0 z-[80] overflow-y-auto" style="display: none;">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" x-on:click="open = false"></div>
        <div class="relative mx-auto mt-16 w-full max-w-lg rounded-2xl border border-slate-800 bg-slate-900 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-800 px-5 py-4">
                <div>
                    <p class="text-sm font-semibold text-white">Reasignar puesto</p>
                    <p class="mt-0.5 text-xs text-slate-500">{{ $employee->fullName() }} · {{ $employee->document_type }} {{ $employee->document_number }}</p>
                </div>
                <button type="button" class="text-slate-400 hover:text-white" x-on:click="open = false">✕</button>
            </div>

            <form method="POST" action="{{ route('company.employees.reassign', $employee) }}" class="space-y-3 p-5">
                @csrf
                <div>
                    <label class="block text-xs text-slate-400 mb-1">Cliente</label>
                    <select name="client_id" x-model="clientId" x-on:change="onClientChange()" required class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                        <option value="">Seleccione</option>
                        <template x-for="client in tree" :key="client.id">
                            <option :value="client.id" x-text="client.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-slate-400 mb-1">Instalación</label>
                    <select name="installation_id" x-model="installationId" x-on:change="onInstallationChange()" required class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                        <option value="">Seleccione</option>
                        <template x-for="installation in installations" :key="installation.id">
                            <option :value="installation.id" x-text="installation.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-slate-400 mb-1">Puesto</label>
                    <select name="supervisor_post_id" x-model="postId" required class="w-full rounded-lg bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                        <option value="">Seleccione</option>
                        <template x-for="post in posts" :key="post.id">
                            <option :value="post.id" x-text="post.name"></option>
                        </template>
                    </select>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="rounded-lg px-3 py-1.5 text-xs text-slate-400 hover:text-white" x-on:click="open = false">Cancelar</button>
                    <button type="submit" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
