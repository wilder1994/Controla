@php
    use App\Support\Privacy\MinorPersonalData;
@endphp
<div class="rounded-lg border border-amber-800/70 bg-amber-950/30 px-3 py-2">
    <p class="text-xs font-medium text-amber-100">Protección de datos de menores</p>
    <p class="mt-1 text-xs text-amber-200 leading-relaxed whitespace-pre-line">{{ MinorPersonalData::notice() }}</p>
</div>
