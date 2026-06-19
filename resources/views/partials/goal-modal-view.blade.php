<div
    data-modal-open="true"
    class="fixed inset-0 z-40 overflow-y-auto overscroll-contain"
    wire:keydown.escape="closeViewModal">

    {{-- затемнение + клик по фону --}}
    <div class="fixed inset-0 bg-black/60" wire:click="closeViewModal"></div>

    {{-- центрирование + отступы --}}
    <div class="relative flex min-h-[100dvh] items-center justify-center p-4">
        <div
            class="relative z-50 w-full max-w-xl bg-slate-800 rounded-3xl p-6 shadow-2xl border border-slate-700 overflow-y-auto overscroll-contain"
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

                    <p class="text-sm text-slate-400 italic">
                        @if($viewingGoal->author_name)
                            <span>Автор: {{ $viewingGoal->author_name }} | </span>
                        @endif
                        @if($viewingGoal->created_at)
                            {{ $viewingGoal->created_at->format('d.m.Y') }} ({{ (int) $viewingGoal->created_at->diffInDays(now()) }} дн. назад)
                        @endif
                    </p>

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
                        <button
                            type="button"
                            wire:click="togglePublic({{ $viewingGoal->id }})"
                            aria-pressed="{{ $viewingGoal->is_public ? 'true' : 'false' }}"
                            class="text-[11px] px-3 py-1 rounded-full border inline-flex items-center gap-1
                                {{ $viewingGoal->is_public ? 'bg-emerald-500/20 border-emerald-400 text-emerald-200' : 'bg-slate-800 border-slate-600 text-slate-300' }}">
                            <span class="w-2 h-2 rounded-full {{ $viewingGoal->is_public ? 'bg-emerald-300' : 'bg-slate-500' }}"></span>
                            {{ $viewingGoal->is_public ? 'Публичная' : 'Частная' }}
                        </button>
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

                {{-- Подзадачи --}}
                @php
                    $subgoals = collect($viewingGoal->subgoals ?? [])
                        ->sortBy('position')
                        ->values();
                    $completedSubgoals = $subgoals->where('is_done', true)->count();
                @endphp
                <div
                    data-subgoals-board
                    data-goal-id="{{ $viewingGoal->id }}"
                    x-data="{
                        resizeSubgoalTitle(field) {
                            if (!field) return;
                            field.style.height = '1.75rem';
                            field.style.height = Math.max(field.scrollHeight, 28) + 'px';
                        },
                        resizeSubgoalTitles() {
                            this.$el.querySelectorAll('[data-subgoal-title]').forEach((field) => this.resizeSubgoalTitle(field));
                        },
                    }"
                    x-init="$nextTick(() => {
                        resizeSubgoalTitles();
                        requestAnimationFrame(() => resizeSubgoalTitles());
                        setTimeout(() => resizeSubgoalTitles(), 150);
                    })"
                    x-on:subgoal-created.window="
                        if (Number($event.detail.goalId) !== Number($el.dataset.goalId)) return;
                        requestAnimationFrame(() => {
                            const selector = '[data-subgoal-id=&quot;' + $event.detail.subgoalId + '&quot;] [data-subgoal-title]';
                            const field = $el.querySelector(selector);
                            if (!field) return;
                            resizeSubgoalTitle(field);
                            setTimeout(() => resizeSubgoalTitle(field), 150);
                            field.focus();
                            field.select();
                        });
                    "
                    class="space-y-3">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <h3 class="text-sm font-semibold text-slate-300">
                            Подзадачи {{ $completedSubgoals }}/{{ $subgoals->count() }}
                        </h3>
                        <button
                            type="button"
                            wire:click="requestSubgoalCostsSync({{ $viewingGoal->id }})"
                            class="inline-flex items-center justify-center px-3 py-1.5 rounded-xl border border-slate-600 text-xs font-medium text-slate-200 hover:bg-slate-700/60 hover:border-slate-500 transition disabled:opacity-50 disabled:cursor-not-allowed"
                            title="Посчитать рублевые суммы из подзадач и записать в целевую сумму"
                            {{ $subgoals->isEmpty() ? 'disabled' : '' }}>
                            Затраты в цель
                        </button>
                    </div>

                    <div
                        data-subgoals-list
                        class="rounded-2xl border border-slate-700 bg-slate-900/40 divide-y divide-slate-700">
                        @forelse($subgoals as $subgoal)
                            @php
                                $subgoalId = (string) ($subgoal['id'] ?? '');
                                $isDone = (bool) ($subgoal['is_done'] ?? false);
                                $title = trim((string) preg_replace('/\s+/u', ' ', (string) ($subgoal['title'] ?? 'Новая подзадача')));
                                $title = $title !== '' ? $title : 'Новая подзадача';
                            @endphp
                            <div
                                data-subgoal-item
                                data-subgoal-id="{{ $subgoalId }}"
                                draggable="true"
                                class="group flex items-center gap-2 px-3 py-2 {{ $isDone ? 'opacity-80' : '' }}">
                                <span
                                    data-subgoal-drag-handle
                                    draggable="true"
                                    class="select-none text-slate-500 hover:text-slate-300 px-1 py-1 rounded cursor-grab active:cursor-grabbing"
                                    title="Перетащить">
                                    &#8942;&#8942;
                                </span>
                                <button
                                    type="button"
                                    wire:click="toggleSubgoal({{ $viewingGoal->id }}, '{{ $subgoalId }}')"
                                    class="my-1 w-6 h-6 shrink-0 rounded-full border inline-flex items-center justify-center transition
                                        {{ $isDone ? 'bg-emerald-500 border-emerald-400 text-white' : 'border-slate-500 text-transparent hover:border-slate-300 hover:text-slate-300' }}"
                                    title="Переключить статус">
                                    &#10003;
                                </button>

                                <textarea
                                    data-subgoal-title
                                    rows="1"
                                    wrap="soft"
                                    x-init="$nextTick(() => {
                                        resizeSubgoalTitle($el);
                                        requestAnimationFrame(() => resizeSubgoalTitle($el));
                                        setTimeout(() => resizeSubgoalTitle($el), 150);
                                    })"
                                    x-on:input="resizeSubgoalTitle($el)"
                                    @keydown.enter.prevent="$wire.saveSubgoalAndAddNext({{ $viewingGoal->id }}, '{{ $subgoalId }}', $el.value)"
                                    @blur="$wire.renameSubgoal({{ $viewingGoal->id }}, '{{ $subgoalId }}', $el.value)"
                                    class="mt-[-4.5px] flex-1 min-w-0 resize-none overflow-hidden break-words bg-transparent text-sm leading-5 px-1 py-1 rounded-lg border border-transparent focus:border-indigo-400/70 focus:bg-slate-800/60 focus:outline-none {{ $isDone ? 'line-through text-slate-500' : 'text-slate-200' }}"
                                    style="height: 1.75rem; min-height: 1.75rem; field-sizing: content;"
                                    aria-label="Название подзадачи">{{ $title }}</textarea>

                                <button
                                    type="button"
                                    wire:click="deleteSubgoal({{ $viewingGoal->id }}, '{{ $subgoalId }}')"
                                    class="w-7 h-7 shrink-0 rounded-lg text-slate-500 hover:text-rose-300 hover:bg-rose-500/10 transition"
                                    title="Удалить подзадачу">
                                    &times;
                                </button>
                            </div>
                        @empty
                            <div class="px-3 py-3 text-xs text-slate-500">
                                Пока нет подзадач
                            </div>
                        @endforelse
                    </div>

                    <button
                        type="button"
                        wire:click="addSubgoal({{ $viewingGoal->id }})"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-500 text-slate-100 hover:bg-slate-700/50 transition">
                        <span class="text-lg leading-none">+</span>
                        <span class="text-xs font-medium">Подзадача</span>
                    </button>
                </div>

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

    @if($confirmSubgoalCostsGoalId === $viewingGoal->id)
        <div class="fixed inset-0 z-[70] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/70" wire:click="cancelSubgoalCostsSync"></div>
            <div class="relative w-full max-w-md rounded-2xl border border-slate-700 bg-slate-800 p-5 shadow-2xl">
                <h3 class="text-base font-semibold text-slate-100">
                    Перенести сумму затрат в цель?
                </h3>
                <div class="mt-3 space-y-2 text-sm leading-6 text-slate-300">
                    <p>
                        Система прочитает все подзадачи и найдёт рублёвые суммы вида
                        <span class="text-slate-100">+1000₽</span>,
                        <span class="text-slate-100">-1000₽</span>,
                        <span class="text-slate-100">1000₽</span>,
                        <span class="text-slate-100">1000руб</span> или
                        <span class="text-slate-100">1000р.</span>.
                    </p>
                    <p>
                        Суммы с минусом будут вычтены, остальные прибавлены. Итог заменит текущую целевую сумму цели.
                    </p>
                    @if($viewingGoal->is_completed)
                        <p class="text-amber-200">
                            Цель уже выполнена, поэтому достигнутая сумма тоже будет обновлена до новой целевой суммы.
                        </p>
                    @endif
                </div>
                <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        wire:click="cancelSubgoalCostsSync"
                        class="px-4 py-2 rounded-xl bg-slate-700 text-sm text-slate-100 hover:bg-slate-600 transition">
                        Отмена
                    </button>
                    <button
                        type="button"
                        wire:click="confirmSubgoalCostsSync"
                        class="px-4 py-2 rounded-xl bg-indigo-500 text-sm font-semibold text-white hover:bg-indigo-600 transition">
                        Подтвердить
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
