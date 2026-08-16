<div class="flex flex-wrap gap-2 mb-4">
    <a href="{{ route('admin.settings.structure-types.index') }}"
       class="rounded-lg px-3 py-1.5 text-xs font-semibold {{ request()->routeIs('admin.settings.structure-types.*') ? 'bg-violet-600 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' }}">
        Tipos de estructura
    </a>
    <a href="{{ route('admin.settings.document-types.index') }}"
       class="rounded-lg px-3 py-1.5 text-xs font-semibold {{ request()->routeIs('admin.settings.document-types.*') ? 'bg-violet-600 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' }}">
        Tipos de documento
    </a>
</div>
