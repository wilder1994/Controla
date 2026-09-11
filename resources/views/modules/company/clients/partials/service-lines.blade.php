@php
    $accessOn = (bool) old('has_access', $accessDefault ?? false);
    $proOn = (bool) old('has_supervision', $proDefault ?? false);
    $accessRemaining = (int) ($metrics['clients_remaining'] ?? 0);
    $proRemaining = (int) ($metrics['supervision_remaining'] ?? 0);
    $accessMax = (int) ($metrics['max_clients'] ?? 0);
    $proMax = (int) ($metrics['max_supervision_clients'] ?? 0);
    $unlimited = (bool) ($metrics['supervision_unlimited'] ?? false);
    $supervisionCap = $unlimited ? 'Ilimitada' : $proRemaining.'/'.$proMax;
    $supervisionDisabled = (! $unlimited && ($proMax < 1 || ($proRemaining < 1 && ! $proOn)));
    $foldersOn = (bool) old('show_personnel_folders', $foldersDefault ?? false);
@endphp

<div>
    <p class="text-sm font-medium text-white">Líneas de servicio</p>
    <p class="text-xs text-slate-500 mt-0.5">La ficha no consume cupo. El cupo se usa al marcar Accesos o Supervisión.</p>
    <x-ui.field-error :messages="$errors->get('has_access')" />
    <x-ui.field-error :messages="$errors->get('has_supervision')" />

    <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
        <label class="rounded-xl border px-4 py-3 cursor-pointer {{ $accessOn ? 'border-indigo-500 bg-indigo-950/30' : 'border-slate-800 bg-slate-950/40' }}">
            <input type="hidden" name="has_access" value="0">
            <input type="checkbox" name="has_access" value="1" class="rounded border-slate-600 text-indigo-600" @checked($accessOn) @disabled(($accessMax < 1 || ($accessRemaining < 1 && ! $accessOn)))>
            <span class="ml-2 text-sm font-semibold text-white">Accesos</span>
            <p class="mt-1 text-[11px] text-slate-400 leading-relaxed">Portería, censo y puntos de acceso. Cupo {{ $accessRemaining }}/{{ $accessMax }}.</p>
        </label>
        <label class="rounded-xl border px-4 py-3 cursor-pointer {{ $proOn ? 'border-amber-500 bg-amber-950/20' : 'border-slate-800 bg-slate-950/40' }}">
            <input type="hidden" name="has_supervision" value="0">
            <input type="checkbox" name="has_supervision" value="1" class="rounded border-slate-600 text-amber-500" @checked($proOn) @disabled($supervisionDisabled)>
            <span class="ml-2 text-sm font-semibold text-white">Supervisión</span>
            <p class="mt-1 text-[11px] text-slate-400 leading-relaxed">App, GPS y revista en puestos de Supervisión. Cupo {{ $supervisionCap }}.</p>
        </label>
    </div>

    <label id="personnel-folders-flag" class="mt-3 flex items-start gap-3 rounded-xl border px-4 py-3 {{ $accessOn ? '' : 'hidden' }} {{ $foldersOn ? 'border-indigo-500 bg-indigo-950/30' : 'border-slate-800 bg-slate-950/40' }}">
        <input type="hidden" name="show_personnel_folders" value="0">
        <input type="checkbox" name="show_personnel_folders" value="1" class="mt-0.5 rounded border-slate-600 text-indigo-600" @checked($foldersOn && $accessOn)>
        <span>
            <span class="text-sm font-semibold text-white">Mostrar indexación de carpetas</span>
            <p class="mt-1 text-[11px] text-slate-400 leading-relaxed">En Operar cliente, el conjunto ve las carpetas de empleados asignados a un puesto de este cliente. Solo lectura. Requiere Accesos.</p>
        </span>
    </label>
    <script>
        (() => {
            const access = document.querySelector('input[name="has_access"][type="checkbox"]');
            const wrap = document.getElementById('personnel-folders-flag');
            if (!access || !wrap) return;
            const sync = () => wrap.classList.toggle('hidden', !access.checked);
            access.addEventListener('change', sync);
            sync();
        })();
    </script>
</div>
