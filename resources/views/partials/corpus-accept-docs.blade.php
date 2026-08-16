@php
    use App\Enums\LegalCorpusType;
@endphp

@if ($corpus->isNotEmpty())
    <div class="space-y-2">
        @foreach ($corpus as $doc)
            @php
                $typeKey = $doc->type instanceof LegalCorpusType ? $doc->type->value : (string) $doc->type;
            @endphp
            <div class="rounded-lg border border-slate-800 bg-slate-900/60 p-3">
                <div class="flex items-start gap-3">
                    <input
                        type="checkbox"
                        name="accept_docs[{{ $typeKey }}]"
                        value="1"
                        id="accept_doc_{{ $typeKey }}"
                        class="mt-1 rounded border-slate-600 bg-slate-950 shrink-0"
                        @checked(old('accept_docs.'.$typeKey))
                        required
                    />
                    <details class="min-w-0 flex-1 text-xs text-slate-400">
                        <summary class="cursor-pointer text-slate-300 font-medium list-none flex items-center gap-2">
                            <span class="text-slate-500 select-none">▸</span>
                            <span>
                                {{ $doc->title }}
                                <span class="text-slate-500 font-normal">(v{{ $doc->version }})</span>
                            </span>
                        </summary>
                        <div class="mt-2 max-h-48 overflow-y-auto whitespace-pre-line text-slate-400 leading-relaxed border-t border-slate-800 pt-2">
                            {{ $doc->content }}
                        </div>
                    </details>
                </div>
                @error('accept_docs.'.$typeKey)
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>
        @endforeach
        @error('accept_docs')
            <p class="text-xs text-rose-400">{{ $message }}</p>
        @enderror
    </div>
@endif
