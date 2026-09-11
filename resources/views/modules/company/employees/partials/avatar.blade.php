@php
    $employee = $employee ?? null;
    $canEdit = $canEdit ?? false;
    $autosubmit = $autosubmit ?? false;
    $action = $action ?? null;
    $src = $employee?->photoUrl();
    $initials = $employee?->initials() ?? '';
    $inputId = $autosubmit ? 'employee-photo-autosubmit' : 'photo';
@endphp

@if ($canEdit && $autosubmit && $action)
    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="relative inline-flex">
        @csrf
        <label class="group relative inline-flex h-24 w-24 cursor-pointer items-center justify-center overflow-hidden rounded-full border border-slate-600 bg-slate-800" title="Cambiar foto">
            @if ($src)
                <img src="{{ $src }}" alt="" class="h-full w-full object-cover">
            @else
                <span class="text-lg font-semibold text-slate-300">{{ $initials !== '' ? $initials : '+' }}</span>
            @endif
            <span class="absolute bottom-1 right-1 flex h-7 w-7 items-center justify-center rounded-full bg-indigo-600 text-white shadow">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 8h3l2-2h6l2 2h3v11H4V8Z"/><circle cx="12" cy="13" r="3.2"/>
                </svg>
            </span>
            <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" id="{{ $inputId }}" class="sr-only" onchange="this.form.submit()">
        </label>
    </form>
@elseif ($canEdit)
    <label class="group relative inline-flex h-24 w-24 cursor-pointer items-center justify-center overflow-hidden rounded-full border border-slate-600 bg-slate-800" title="Subir foto">
        <img
            x-show="photoPreview"
            :src="photoPreview"
            alt=""
            class="h-full w-full object-cover"
            @if (! $src) style="display:none" @endif
        >
        <span x-show="!photoPreview" class="text-lg font-semibold text-slate-300">{{ $initials !== '' ? $initials : '+' }}</span>
        <span class="absolute bottom-1 right-1 flex h-7 w-7 items-center justify-center rounded-full bg-indigo-600 text-white shadow">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 8h3l2-2h6l2 2h3v11H4V8Z"/><circle cx="12" cy="13" r="3.2"/>
            </svg>
        </span>
        <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" id="{{ $inputId }}" class="sr-only" @change="previewPhoto($event)">
    </label>
@else
    <span class="inline-flex h-24 w-24 items-center justify-center overflow-hidden rounded-full border border-slate-600 bg-slate-800">
        @if ($src)
            <img src="{{ $src }}" alt="" class="h-full w-full object-cover">
        @else
            <span class="text-lg font-semibold text-slate-300">{{ $initials }}</span>
        @endif
    </span>
@endif
