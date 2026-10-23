<div
    data-modal-open="true"
    class="fixed inset-0 z-40 overflow-y-auto overscroll-contain"
    wire:keydown.escape="closeBoardSettings">
    <div class="fixed inset-0 bg-black/60" wire:click="closeBoardSettings"></div>

    <div class="relative flex min-h-[100dvh] items-center justify-center p-4">
        <div
            role="dialog"
            aria-modal="true"
            aria-labelledby="board-settings-title"
            wire:key="board-settings-{{ $currentBoard->id }}"
            x-data="{
                copied: false,
                copyError: false,
                keyReset: false,
                async copyKey() {
                    this.copied = false;
                    this.copyError = false;

                    try {
                        if (navigator.clipboard && window.isSecureContext) {
                            await navigator.clipboard.writeText(this.$refs.apiKey.value);
                        } else {
                            this.$refs.apiKey.focus();
                            this.$refs.apiKey.select();
                            if (!document.execCommand('copy')) throw new Error('copy failed');
                        }

                        this.copied = true;
                        setTimeout(() => this.copied = false, 2000);
                    } catch {
                        this.copyError = true;
                        this.$refs.apiKey.focus();
                        this.$refs.apiKey.select();
                    }
                },
            }"
            x-init="$nextTick(() => $refs.closeSettings.focus({ preventScroll: true }))"
            x-on:board-api-key-reset.window="copied = false; copyError = false; keyReset = true"
            class="relative z-50 w-full max-w-xl rounded-3xl border border-slate-700 bg-slate-800 p-6 shadow-2xl overflow-y-auto overscroll-contain"
            style="max-height: calc(100dvh - 2rem - env(safe-area-inset-bottom)); -webkit-overflow-scrolling: touch;">
            <div class="mb-6 flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <h2 id="board-settings-title" class="text-lg font-semibold text-slate-100">Настройки доски</h2>
                    <p class="mt-1 break-words text-sm text-slate-400">{{ $currentBoard->name }}</p>
                </div>
                <button
                    type="button"
                    x-ref="closeSettings"
                    wire:click="closeBoardSettings"
                    aria-label="Закрыть настройки"
                    class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-700/60 hover:text-slate-100">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" d="m6 6 12 12M18 6 6 18" />
                    </svg>
                </button>
            </div>

            <div class="rounded-2xl border border-slate-700 bg-slate-900/40 p-4">
                <label for="board-api-key" class="block text-sm font-medium text-slate-200">API-ключ</label>
                <p id="board-api-key-help" class="mt-1 text-xs leading-5 text-slate-400">Создание цели: <code>POST /api/goals</code>. Передавайте ключ в заголовке <code>Authorization: Bearer …</code>.</p>

                <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                    <input
                        id="board-api-key"
                        x-ref="apiKey"
                        type="text"
                        readonly
                        spellcheck="false"
                        autocomplete="off"
                        aria-describedby="board-api-key-help"
                        value="{{ $currentBoard->api_key }}"
                        x-on:click="$el.select()"
                        class="min-w-0 w-full flex-1 rounded-xl border border-slate-600 bg-slate-900 px-3 py-2 font-mono text-xs leading-5 text-slate-200 outline-none focus:border-indigo-400 focus:ring-1 focus:ring-indigo-400">
                    <button
                        type="button"
                        x-on:click="copyKey()"
                        class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-slate-600 bg-slate-800 px-3 py-2 text-xs font-medium text-slate-200 transition hover:border-slate-500 hover:bg-slate-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <rect x="8" y="8" width="12" height="12" rx="2" />
                            <path stroke-linecap="round" d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3" />
                        </svg>
                        <span x-text="copied ? 'Скопировано' : 'Копировать'">Копировать</span>
                    </button>
                </div>

                <p x-show="copyError" style="display: none;" role="status" class="mt-2 text-xs text-amber-300">Ключ выделен. Скопируйте его вручную.</p>
                <p x-show="keyReset" style="display: none;" role="status" class="mt-2 text-xs text-emerald-300">Новый ключ создан.</p>

                <div class="mt-4 flex flex-col gap-3 border-t border-slate-700 pt-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs leading-5 text-slate-500">Сброс создаст новый ключ вместо текущего.</p>
                    <button
                        type="button"
                        wire:click="resetBoardApiKey"
                        wire:confirm="Сбросить ключ? Текущий ключ будет заменён новым."
                        wire:loading.attr="disabled"
                        wire:target="resetBoardApiKey"
                        class="shrink-0 rounded-xl border border-slate-600 px-3 py-2 text-xs font-medium text-slate-300 transition hover:border-red-400/60 hover:bg-red-500/10 hover:text-red-200 disabled:opacity-50">
                        <span wire:loading.remove wire:target="resetBoardApiKey">Сбросить ключ</span>
                        <span wire:loading wire:target="resetBoardApiKey">Создаём ключ…</span>
                    </button>
                </div>
            </div>

            <div class="mt-5 flex justify-end">
                <button type="button" wire:click="closeBoardSettings" class="rounded-xl bg-slate-700 px-4 py-2 text-sm text-slate-200 transition hover:bg-slate-600">Закрыть</button>
            </div>
        </div>
    </div>
</div>
