<x-client-layout title="Importar autorizaciones">
    <div class="max-w-xl space-y-4">
        <h2 class="text-2xl font-bold text-white">Importar desde Excel</h2>
        <p class="text-sm text-slate-400">Columnas esperadas: <span class="font-mono text-slate-300">visitante, estructura, fecha</span> (opcional: documento, categoria, anfitrion_documento).</p>
        <form action="{{ route('client.authorizations.import.store') }}" method="POST" enctype="multipart/form-data" class="rounded-xl border border-slate-800 bg-slate-900 p-6 space-y-4">
            @csrf
            <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="block w-full text-sm text-slate-300 file:mr-4 file:rounded-lg file:border-0 file:bg-teal-600 file:px-4 file:py-2 file:text-white">
            <button type="submit" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-500">Importar</button>
        </form>
    </div>
</x-client-layout>
