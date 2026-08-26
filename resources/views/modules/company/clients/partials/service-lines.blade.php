@php
    $accessOn = (bool) old('has_access', $accessDefault ?? false);
    $proOn = (bool) old('has_supervision', $proDefault ?? false);
    $accessRemaining = (int) ($metrics['clients_remaining'] ?? 0);
    $proRemaining = (int) ($metrics['supervision_remaining'] ?? 0);
    $accessMax = (int) ($metrics['max_clients'] ?? 0);
    $proMax = (int) ($metrics['max_supervision_clients'] ?? 0);
@endphp

<div>
    <p class="text-sm font-medium text-white">Líneas de servicio</p>
    <p class="text-xs text-slate-500 mt-0.5">La ficha no consume cupo. El cupo se usa al marcar Accesos o Supervisión Pro.</p>
    <x-ui.field-error :messages="$errors->get('has_access')" />
    <x-ui.field-error :messages="$errors->get('has_supervision')" />

    <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
        <label class="rounded-xl border px-4 py-3 cursor-pointer {{ $accessOn ? 'border-indigo-500 bg-indigo-950/30' : 'border-slate-800 bg-slate-950/40' }}">
            <input type="hidden" name="has_access" value="0">
            <input type="checkbox" name="has_access" value="1" class="rounded border-slate-600 text-indigo-600" @checked($accessOn) @disabled(($accessMax < 1 || ($accessRemaining < 1 && ! $accessOn)))>
            <span class="ml-2 text-sm font-semibold text-white">Accesos</span>
            <p class="mt-1 text-[11px] text-slate-400 leading-relaxed">Portería, censo y supervisión básica en puesto. Cupo {{ $accessRemaining }}/{{ $accessMax }}.</p>
        </label>
        <label class="rounded-xl border px-4 py-3 cursor-pointer {{ $proOn ? 'border-amber-500 bg-amber-950/20' : 'border-slate-800 bg-slate-950/40' }}">
            <input type="hidden" name="has_supervision" value="0">
            <input type="checkbox" name="has_supervision" value="1" class="rounded border-slate-600 text-amber-500" @checked($proOn) @disabled(($proMax < 1 || ($proRemaining < 1 && ! $proOn)))>
            <span class="ml-2 text-sm font-semibold text-white">Supervisión Pro</span>
            <p class="mt-1 text-[11px] text-slate-400 leading-relaxed">App, GPS y revista en campo (llena el puesto). Cupo {{ $proRemaining }}/{{ $proMax }}.</p>
        </label>
    </div>
</div>
