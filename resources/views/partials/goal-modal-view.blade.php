<div
    data-modal-open="true"
    class="fixed inset-0 z-40 overflow-y-auto overscroll-contain"
    wire:keydown.escape="closeViewModal">

    {{-- затемнение + клик по фону --}}
    <div class="fixed inset-0 bg-black/60" wire:click="closeViewModal"></div>

    {{-- центрирование + отступы --}}
    <div class="relative flex min-h-[100dvh] items-center justify-center p-4">
        <div
            class="relative z-50 w-full max-w-lg bg-slate-800 rounded-3xl p-6 shadow-2xl border border-slate-700 overflow-y-auto overscroll-contain"
            style="max-height: calc(100dvh - 2rem - env(safe-area-inset-bottom)); -webkit-overflow-scrolling: touch;">
            <button
                type="button"
                wire:click="closeViewModal"
                class="absolute top-6 right-6 text-slate-400 hover:text-slate-200 text-2xl leading-none">
                &times;
            </button>

            <div class="space-y-6">
                {{-- Заголовок и статус --}}
                <div>
                    <div class="flex items-start justify-between gap-4 mb-3">
                        <h2 class="text-2xl font-bold text-slate-100 {{ $viewingGoal->is_completed ? 'line-through text-slate-400' : '' }}">
                            {{ $viewingGoal->title }}
                        </h2>
                    </div>

                    @if($viewingGoal->author_name)
                        <p class="text-sm text-slate-400 italic">Автор: {{ $viewingGoal->author_name }}</p>
                    @endif

                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        @if($viewingGoal->is_completed)
                        <span class="px-3 py-1 rounded-full text-[11px] bg-emerald-500/20 border border-emerald-400 text-emerald-200">
                            ✓ Выполнено
                        </span>
                        @else
                        <span class="px-3 py-1 rounded-full text-[11px] bg-slate-600/50 border border-slate-500 text-slate-200">
                            В процессе
                        </span>
                        @endif
                        <span class="px-3 py-1 rounded-full text-[11px] whitespace-nowrap
                                {{ match($viewingGoal->priority) {
                                    1 => 'bg-red-500/20 border border-red-400 text-red-200',
                                    2 => 'bg-amber-500/20 border border-amber-400 text-amber-200',
                                    3 => 'bg-emerald-500/20 border border-emerald-400 text-emerald-200',
                                    4 => 'bg-slate-600/50 border border-slate-500 text-slate-200',
                                    default => ''
                                } }}">
                            {{ match($viewingGoal->priority) {
                                    1 => 'Высокий приоритет',
                                    2 => 'Средний приоритет',
                                    3 => 'Низкий приоритет',
                                    4 => 'Архив',
                                    default => ''
                                } }}
                        </span>
                        <span
                            type="button"
                            class="text-[11px] px-3 py-1 rounded-full border inline-flex items-center gap-1
                                {{ $viewingGoal->is_public ? 'bg-emerald-500/20 border-emerald-400 text-emerald-200' : 'bg-slate-800 border-slate-600 text-slate-300' }}">
                            <span class="w-2 h-2 rounded-full {{ $viewingGoal->is_public ? 'bg-emerald-300' : 'bg-slate-500' }}"></span>
                            {{ $viewingGoal->is_public ? 'Публичная' : 'Частная' }}
                        </span>
                    </div>
                    @if($viewingGoal->is_public && $viewingGoal->public_token)
                        <div class="mt-3 flex items-center gap-2">
                            <input
                                type="text"
                                readonly
                                value="{{ route('goals.share', $viewingGoal->public_token) }}"
                                onclick="this.select(); this.setSelectionRange(0, this.value.length); if (navigator.clipboard && navigator.clipboard.writeText) { navigator.clipboard.writeText(this.value); }"
                                onfocus="this.select();"
                                class="w-[65%] text-xs px-3 py-2 rounded-xl border border-indigo-400/70 text-indigo-100 bg-indigo-500/10 focus:outline-none focus:ring-2 focus:ring-indigo-400/60 cursor-pointer"
                                aria-label="Ссылка для копирования">
                            <a
                                href="{{ route('goals.share', $viewingGoal->public_token) }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                aria-label="Перейти по ссылке"
                                class="text-xs px-3 py-2 rounded-xl border border-indigo-400/70 text-indigo-100 bg-indigo-500/15 hover:bg-indigo-500/30 hover:border-indigo-300 transition inline-flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M14 3h7m0 0v7m0-7L10 14" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 5v14h14" />
                                </svg>
                            </a>
                        </div>
                    @endif
                </div>

                {{-- Описание --}}
                @if($viewingGoal->description)
                <div>
                    <h3 class="text-sm font-semibold text-slate-300 mb-2">Описание</h3>
                    <p class="text-sm text-slate-300 leading-relaxed bg-slate-900/50 rounded-2xl p-4">
                        {!! nl2br($viewingGoal->description) !!}
                    </p>
                </div>
                @endif

                {{-- Финансовая информация --}}
                @if($viewingGoal->target_amount > 0)
                <div>
                    <h3 class="text-sm font-semibold text-slate-300 mb-3">Финансы</h3>
                    <div class="bg-slate-900/50 rounded-2xl p-4 space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-slate-400">Целевая сумма:</span>
                            <span class="text-base font-semibold text-slate-100">{{ number_format($viewingGoal->target_amount, 0, ',', ' ') }} ₽</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-slate-400">Достигнуто:</span>
                            <span class="text-base font-semibold text-emerald-400">{{ number_format($viewingGoal->saved_amount, 0, ',', ' ') }} ₽</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-slate-400">Осталось:</span>
                            <span class="text-base font-semibold text-indigo-400">{{ number_format($viewingGoal->target_amount - $viewingGoal->saved_amount, 0, ',', ' ') }} ₽</span>
                        </div>

                        <div class="pt-2">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-xs text-slate-400">Прогресс</span>
                                <span class="text-xs font-semibold text-slate-300">{{ $viewingGoal->progress_percent }}%</span>
                            </div>
                            <div class="w-full h-3 rounded-full bg-slate-800 overflow-hidden border border-slate-700">
                                <div
                                    class="h-full bg-gradient-to-r from-emerald-500 to-emerald-400 transition-all"
                                    style="width: {{ $viewingGoal->progress_percent }}%;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- URL --}}
                @if($viewingGoal->url)
                <div>
                    <h3 class="text-sm font-semibold text-slate-300 mb-2">Ссылка</h3>
                    <a
                        href="{{ $viewingGoal->url }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl bg-indigo-500/20 border border-indigo-400/70 text-indigo-200 hover:bg-indigo-500/30 hover:border-indigo-300 transition text-sm">
                        <span>Открыть ссылку</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M14 3h7m0 0v7m0-7L10 14" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 5v14h14" />
                        </svg>
                    </a>
                </div>
                @endif

                {{-- Кнопки действий --}}
                <div class="flex gap-3 justify-end pt-4 border-t border-slate-700">
                    <button
                        type="button"
                        wire:click="openEdit({{ $viewingGoal->id }})"
                        class="px-4 py-2 rounded-2xl bg-indigo-500 hover:bg-indigo-600 text-sm font-semibold text-white transition">
                        Редактировать
                    </button>
                    <button
                        type="button"
                        wire:click="closeViewModal"
                        class="px-4 py-2 rounded-2xl bg-slate-700 hover:bg-slate-600 text-sm transition">
                        Закрыть
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>