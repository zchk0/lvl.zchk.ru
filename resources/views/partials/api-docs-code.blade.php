<div
    @isset($id) id="{{ $id }}" @endisset
    data-code-block
    class="min-w-0 overflow-hidden rounded-2xl border border-slate-800 bg-slate-950">
    <div class="flex items-center justify-between gap-3 border-b border-slate-800 px-4 py-2.5">
        <span class="text-xs font-medium text-slate-400">{{ $label }}</span>
        <button
            type="button"
            data-copy-code
            aria-label="Копировать пример: {{ $label }}"
            class="inline-flex shrink-0 items-center gap-1.5 rounded-md px-2 py-1 text-xs text-slate-400 transition hover:bg-slate-800 hover:text-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400/60">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <rect x="8" y="8" width="12" height="12" rx="2" />
                <path stroke-linecap="round" d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3" />
            </svg>
            <span data-copy-label>Копировать</span>
        </button>
    </div>
    <pre class="overflow-x-auto p-4 text-xs leading-6 text-slate-300 sm:p-5 sm:text-[13px]"><code>{{ $code }}</code></pre>
    <span data-copy-status role="status" class="sr-only"></span>
</div>
