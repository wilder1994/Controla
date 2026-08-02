<div class="mb-6 flex gap-2 max-w-xl">
    @foreach ([1 => 'Datos', 2 => 'Legal', 3 => 'Pago'] as $n => $label)
        <div class="flex-1">
            <div class="h-1 rounded-full {{ $n <= $step ? 'bg-cyan-500' : 'bg-slate-700' }}"></div>
            <p class="mt-1 text-[10px] text-slate-500">{{ $label }}</p>
        </div>
    @endforeach
</div>
