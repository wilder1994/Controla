@php
    $userName = Auth::user()?->name ?? '';
@endphp
<div class="px-4 py-3 border-t border-slate-800 shrink-0 min-w-0">
    <p class="text-xs text-slate-400 truncate" title="{{ $userName }}">{{ $userName }}</p>
    <form method="POST" action="{{ route('logout') }}" class="mt-1">
        @csrf
        <button type="submit" class="text-xs text-slate-500 hover:text-white transition">
            Cerrar sesión
        </button>
    </form>
</div>
