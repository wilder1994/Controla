<div
    x-data="{
        open: @js($errors->has('file') || $errors->has('paste')),
        dragging: false,
        fileName: '',
        setFile(file) {
            if (! file) return;
            this.fileName = file.name;
            const transfer = new DataTransfer();
            transfer.items.add(file);
            this.$refs.file.files = transfer.files;
        },
        clearFile() {
            this.fileName = '';
            this.$refs.file.value = '';
        },
    }"
    x-on:open-client-import.window="open = true"
    x-on:keydown.escape.window="if (open) open = false"
>
    <div
        x-show="open"
        x-cloak
        class="fixed inset-0 z-[80] overflow-y-auto"
        style="display: none;"
    >
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" x-on:click="open = false"></div>
        <div class="relative mx-auto mt-16 w-full max-w-lg rounded-2xl border border-slate-800 bg-slate-900 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-800 px-5 py-4">
                <div>
                    <p class="text-sm font-semibold text-white">Carga masiva de clientes</p>
                    <p class="mt-0.5 text-xs text-slate-500">Nada se guarda hasta que revises y aceptes. La ficha no consume cupo.</p>
                </div>
                <button type="button" class="text-slate-400 hover:text-white" x-on:click="open = false">✕</button>
            </div>

            <form method="POST" action="{{ route('company.clients.import.preview.store') }}" enctype="multipart/form-data" class="space-y-4 p-5">
                @csrf
                <label
                    class="flex min-h-[9rem] cursor-pointer flex-col items-center justify-center rounded-xl border border-dashed px-4 py-6 text-center transition"
                    :class="dragging
                        ? 'border-indigo-400 bg-indigo-950/40'
                        : (fileName ? 'border-emerald-500/80 bg-emerald-950/35' : 'border-slate-700 bg-slate-950/60')"
                    x-on:dragenter.prevent="dragging = true"
                    x-on:dragover.prevent="dragging = true"
                    x-on:dragleave.prevent="if (! $event.currentTarget.contains($event.relatedTarget)) dragging = false"
                    x-on:drop.prevent="dragging = false; setFile($event.dataTransfer.files[0])"
                >
                    <input
                        x-ref="file"
                        class="sr-only"
                        type="file"
                        name="file"
                        accept=".xlsx,.xls"
                        x-on:change="setFile($event.target.files[0])"
                    >

                    <div x-show="! fileName" class="flex flex-col items-center">
                        <p class="text-sm text-slate-200">Arrastra el Excel o haz clic para elegir</p>
                        <p class="mt-1 text-xs text-slate-500">Formato .xlsx · hoja Clientes</p>
                    </div>

                    <div x-show="fileName" x-cloak class="flex flex-col items-center">
                        <p class="text-sm font-medium text-emerald-300">Archivo listo</p>
                        <p class="mt-0.5 max-w-full truncate px-2 text-sm text-white" x-text="fileName"></p>
                        <button
                            type="button"
                            class="mt-2 text-xs text-slate-400 underline decoration-slate-600 hover:text-white"
                            x-on:click.stop.prevent="clearFile()"
                        >
                            Quitar
                        </button>
                    </div>
                </label>
                <x-ui.field-error :messages="$errors->get('file')" />

                <div>
                    <x-ui.label for="client-paste">O pega la tabla (copiar desde Excel)</x-ui.label>
                    <textarea
                        id="client-paste"
                        name="paste"
                        rows="4"
                        class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-xs text-white placeholder:text-slate-600 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/30"
                        placeholder="Incluye la fila de encabezados…"
                    >{{ old('paste') }}</textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-1">
                    <button
                        type="button"
                        class="inline-flex h-9 items-center justify-center rounded-lg border border-slate-700 px-4 text-sm font-medium text-slate-200 hover:bg-slate-800"
                        x-on:click="open = false"
                    >
                        Cancelar
                    </button>
                    <x-ui.button type="submit" size="sm">Revisar datos</x-ui.button>
                </div>
            </form>
        </div>
    </div>
</div>
