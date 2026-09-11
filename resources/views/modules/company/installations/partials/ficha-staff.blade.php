@php
    $admins = $installation->staffRoleLines('admin');
    $support = $installation->staffRoleLines('support');
@endphp
<div>
    <dt class="text-xs text-slate-500">Administrador</dt>
    <dd class="text-slate-200 space-y-1">
        @forelse ($admins as $line)
            <p>{{ $line }}</p>
        @empty
            —
        @endforelse
    </dd>
</div>
<div>
    <dt class="text-xs text-slate-500">Apoyo</dt>
    <dd class="text-slate-200 space-y-1">
        @forelse ($support as $line)
            <p>{{ $line }}</p>
        @empty
            —
        @endforelse
    </dd>
</div>
