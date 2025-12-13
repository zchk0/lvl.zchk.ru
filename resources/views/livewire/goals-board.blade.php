<div class="min-h-screen bg-slate-900 text-slate-100 py-10">
    {{-- Глобальный индикатор загрузки Livewire --}}
    <div
        wire:loading.delay
        class="fixed inset-x-0 top-4 z-40 flex justify-center pointer-events-none"
    >
        <div class="px-4 py-2 rounded-full bg-slate-800/95 border border-slate-700 shadow-lg flex items-center gap-2 text-xs text-slate-200">
            <span class="w-3 h-3 rounded-full border-2 border-indigo-400 border-t-transparent animate-spin"></span>
            <span>Синхронизация изменений…</span>
        </div>
    </div>

    @php
        $isAll = $visiblePriority === 'all';
        $colsClass = $isAll ? 'md:grid-cols-3' : 'md:grid-cols-1';
        $widthClass = $isAll ? 'max-w-6xl' : 'max-w-3xl mx-auto';
    @endphp

    <div class="max-w-6xl mx-auto px-4">
        {{-- Заголовок + селект фильтра --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-7">
            <h1 class="text-2xl sm:text-3xl font-semibold">
                Цели v0.27
            </h1>

            <div class="flex items-center gap-2 text-sm">
                <span class="text-slate-400 hidden sm:inline">Отображать:</span>
                <select
                    wire:model.live="visiblePriority"
                    class="rounded-2xl bg-slate-800 border border-slate-600 px-3 py-1.5 text-sm text-slate-100 outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                >
                    <option value="all">Все приоритеты</option>
                    <option value="1">Только высокий</option>
                    <option value="2">Только средний</option>
                    <option value="3">Только низкий</option>
                    <option value="4">Архив</option>
                </select>
            </div>
        </div>

        {{-- КОЛОНКИ (или одна, если выбран фильтр) --}}
        <div class="grid gap-6 {{ "$colsClass $widthClass" }}">
            {{-- Высокий приоритет --}}
            @if($visiblePriority === 'all' || $visiblePriority === '1')
                <div class="bg-slate-800 rounded-3xl p-4 flex flex-col gap-4 shadow-xl">
                    <div class="flex items-center justify-between gap-2">
                        <div>
                            <h2 class="text-lg font-semibold text-red-400">
                                Высокий приоритет
                            </h2>
                            <p class="text-xs text-slate-400">
                                Самое важное, срочные цели
                            </p>
                        </div>
                        <button
                            wire:click="openCreate(1)"
                            wire:loading.attr="disabled"
                            class="px-3 py-1 rounded-2xl text-xs font-semibold bg-red-500 hover:bg-red-600 text-white disabled:opacity-60"
                        >
                            + Добавить
                        </button>
                    </div>

                    @if($highGoals->isNotEmpty())
                        @php
                            $totalTarget = $highGoals->sum('target_amount');
                            $totalSaved  = $highGoals->sum('saved_amount');
                        @endphp
                        <div class="text-xs text-slate-300">
                            Итого: {{ number_format($totalTarget, 0, ',', ' ') }} ₽
                            @if($totalTarget > 0)
                                <span class="text-slate-500 ml-1">
                                    (отложено {{ number_format($totalSaved, 0, ',', ' ') }} ₽)
                                </span>
                            @endif
                        </div>
                    @endif

                    <div class="flex flex-col gap-3">
                        @forelse($highGoals as $goal)
                            @include('partials.goal-card', ['goal' => $goal])
                        @empty
                            <p class="text-xs text-slate-500">
                                Пока ничего нет. Добавь первую цель.
                            </p>
                        @endforelse
                    </div>
                </div>
            @endif

            {{-- Средний приоритет --}}
            @if($visiblePriority === 'all' || $visiblePriority === '2')
                <div class="bg-slate-800 rounded-3xl p-4 flex flex-col gap-4 shadow-xl">
                    <div class="flex items-center justify-between gap-2">
                        <div>
                            <h2 class="text-lg font-semibold text-amber-300">
                                Средний приоритет
                            </h2>
                            <p class="text-xs text-slate-400">
                                Важные, но не горящие задачи
                            </p>
                        </div>
                        <button
                            wire:click="openCreate(2)"
                            wire:loading.attr="disabled"
                            class="px-3 py-1 rounded-2xl text-xs font-semibold bg-amber-400 hover:bg-amber-500 text-slate-900 disabled:opacity-60"
                        >
                            + Добавить
                        </button>
                    </div>

                    @if($mediumGoals->isNotEmpty())
                        @php
                            $totalTarget = $mediumGoals->sum('target_amount');
                            $totalSaved  = $mediumGoals->sum('saved_amount');
                        @endphp
                        <div class="text-xs text-slate-300">
                            Итого: {{ number_format($totalTarget, 0, ',', ' ') }} ₽
                            @if($totalTarget > 0)
                                <span class="text-slate-500 ml-1">
                                    (отложено {{ number_format($totalSaved, 0, ',', ' ') }} ₽)
                                </span>
                            @endif
                        </div>
                    @endif

                    <div class="flex flex-col gap-3">
                        @forelse($mediumGoals as $goal)
                            @include('partials.goal-card', ['goal' => $goal])
                        @empty
                            <p class="text-xs text-slate-500">
                                Пока пусто.
                            </p>
                        @endforelse
                    </div>
                </div>
            @endif

            {{-- Низкий приоритет --}}
            @if($visiblePriority === 'all' || $visiblePriority === '3')
                <div class="bg-slate-800 rounded-3xl p-4 flex flex-col gap-4 shadow-xl">
                    <div class="flex items-center justify-between gap-2">
                        <div>
                            <h2 class="text-lg font-semibold text-emerald-300">
                                Низкий приоритет
                            </h2>
                            <p class="text-xs text-slate-400">
                                «Когда-нибудь, но не сейчас»
                            </p>
                        </div>
                        <button
                            wire:click="openCreate(3)"
                            wire:loading.attr="disabled"
                            class="px-3 py-1 rounded-2xl text-xs font-semibold bg-emerald-400 hover:bg-emerald-500 text-slate-900 disabled:opacity-60"
                        >
                            + Добавить
                        </button>
                    </div>

                    @if($lowGoals->isNotEmpty())
                        @php
                            $totalTarget = $lowGoals->sum('target_amount');
                            $totalSaved  = $lowGoals->sum('saved_amount');
                        @endphp
                        <div class="text-xs text-slate-300">
                            Итого: {{ number_format($totalTarget, 0, ',', ' ') }} ₽
                            @if($totalTarget > 0)
                                <span class="text-slate-500 ml-1">
                                    (отложено {{ number_format($totalSaved, 0, ',', ' ') }} ₽)
                                </span>
                            @endif
                        </div>
                    @endif

                    <div class="flex flex-col gap-3">
                        @forelse($lowGoals as $goal)
                            @include('partials.goal-card', ['goal' => $goal])
                        @empty
                            <p class="text-xs text-slate-500">
                                Здесь можно складывать долгосрочные мечты.
                            </p>
                        @endforelse
                    </div>
                </div>
            @endif

            {{-- Архив --}}
            @if($visiblePriority === '4')
                <div class="bg-slate-800 rounded-3xl p-4 flex flex-col gap-4 shadow-xl">
                    <div class="flex items-center justify-between gap-2">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-300">
                                Архив
                            </h2>
                            <p class="text-xs text-slate-400">
                                Что-то что уже было выполнено или на текущий момент неактуально
                            </p>
                        </div>
                        <button
                            wire:click="openCreate(4)"
                            wire:loading.attr="disabled"
                            class="px-3 py-1 rounded-2xl text-xs font-semibold bg-slate-400 hover:bg-slate-500 text-slate-900 disabled:opacity-60"
                        >
                            + Добавить
                        </button>
                    </div>

                    @if($archive->isNotEmpty())
                        @php
                            $totalTarget = $archive->sum('target_amount');
                            $totalSaved  = $archive->sum('saved_amount');
                        @endphp
                        <div class="text-xs text-slate-300">
                            Итого: {{ number_format($totalTarget, 0, ',', ' ') }} ₽
                            @if($totalTarget > 0)
                                <span class="text-slate-500 ml-1">
                                    (отложено {{ number_format($totalSaved, 0, ',', ' ') }} ₽)
                                </span>
                            @endif
                        </div>
                    @endif

                    <div class="flex flex-col gap-3">
                        @forelse($archive as $goal)
                            @include('partials.goal-card', ['goal' => $goal])
                        @empty
                            <p class="text-xs text-slate-500">
                                Архив пуст... >_>
                            </p>
                        @endforelse
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- МОДАЛКА ДОБАВЛЕНИЯ / РЕДАКТИРОВАНИЯ --}}
    @if($showModal)
        <div
            class="fixed inset-0 z-40 flex items-center justify-center bg-black/60"
            wire:keydown.escape="closeModal"
        >
            <div
                class="absolute inset-0"
                wire:click="closeModal"
            ></div>

            <div
                class="relative z-50 w-full max-w-lg mx-4 bg-slate-800 rounded-3xl p-6 shadow-2xl border border-slate-700"
            >
                <div class="flex items-start justify-between mb-4">
                    <h2 class="text-lg font-semibold">
                        {{ $editingId ? 'Редактирование цели' : 'Новая цель' }}
                    </h2>
                    <button
                        type="button"
                        wire:click="closeModal"
                        class="text-slate-400 hover:text-slate-200 text-xl leading-none"
                    >
                        &times;
                    </button>
                </div>

                <form wire:submit.prevent="save" class="space-y-4">
                    <div>
                        <label class="block text-sm mb-1">Название</label>
                        <input
                            type="text"
                            wire:model.defer="title"
                            class="w-full rounded-2xl bg-slate-900 border px-3 py-2 text-sm outline-none focus:ring-1
                                   @error('title') border-red-500 focus:ring-red-500 @else border-slate-600 focus:border-indigo-500 focus:ring-indigo-500 @enderror"
                        >
                        @error('title')
                            <div class="text-xs text-red-400 mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm mb-1">Описание</label>
                        <textarea
                            placeholder="Подробное описание цели..."
                            rows="3"
                            wire:model.defer="description"
                            class="w-full rounded-2xl bg-slate-900 border border-slate-600 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 resize-none"
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-sm mb-1">URL</label>
                        <input
                            placeholder="Может на ВБ, а может и на сайт с чем-то связанным..."
                            type="text"
                            wire:model.defer="url"
                            class="w-full rounded-2xl bg-slate-900 border border-slate-600 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                        >
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm mb-1">Стоимость цели</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                wire:model.defer="target_amount"
                                class="w-full rounded-2xl bg-slate-900 border px-3 py-2 text-sm outline-none focus:ring-1
                                       @error('target_amount') border-red-500 focus:ring-red-500 @else border-slate-600 focus:border-indigo-500 focus:ring-indigo-500 @enderror"
                            >
                            @error('target_amount')
                                <div class="text-xs text-red-400 mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm mb-1">Уже отложено</label>
                            <input
                                placeholder="Или потрачено"
                                type="number"
                                step="0.01"
                                min="0"
                                wire:model.defer="saved_amount"
                                class="w-full rounded-2xl bg-slate-900 border px-3 py-2 text-sm outline-none focus:ring-1
                                       @error('saved_amount') border-red-500 focus:ring-red-500 @else border-slate-600 focus:border-indigo-500 focus:ring-indigo-500 @enderror"
                            >
                            @error('saved_amount')
                                <div class="text-xs text-red-400 mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm mb-1">Приоритет</label>
                            <select
                                wire:model.defer="priority"
                                class="w-full rounded-2xl bg-slate-900 border border-slate-600 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                            >
                                <option value="1">Высокий</option>
                                <option value="2">Средний</option>
                                <option value="3">Низкий</option>
                                <option value="4">Архив</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-2 mt-5">
                            <input
                                type="checkbox"
                                wire:model.defer="is_completed"
                                id="is_completed"
                                class="rounded border-slate-600 bg-slate-900"
                            >
                            <label for="is_completed" class="text-sm">Выполнено</label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm mb-1">Автор</label>
                        <input
                            placeholder="Кто?"
                            type="text"
                            wire:model.defer="author_name"
                            class="w-full rounded-2xl bg-slate-900 border border-slate-600 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                        >
                    </div>

                    <div class="flex gap-3 mt-4 justify-end">
                        <button
                            type="button"
                            wire:click="closeModal"
                            wire:loading.attr="disabled"
                            class="px-4 py-2 rounded-2xl bg-slate-700 text-sm disabled:opacity-60"
                        >
                            Отменить
                        </button>

                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="save"
                            class="px-4 py-2 rounded-2xl bg-indigo-500 hover:bg-indigo-600 text-sm font-semibold text-white flex items-center gap-2 disabled:opacity-60"
                        >
                            <span wire:loading.remove wire:target="save">
                                {{ $editingId ? 'Сохранить' : 'Создать' }}
                            </span>

                            <span
                                wire:loading.inline
                                wire:target="save"
                                class="flex items-center gap-2"
                            >
                                <span class="w-4 h-4 rounded-full border-2 border-white border-t-transparent animate-spin"></span>
                                <span>Сохраняем…</span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- МОДАЛКА ПРОСМОТРА --}}
    @if($showViewModal && $viewingGoal)
        <div
            class="fixed inset-0 z-40 flex items-center justify-center bg-black/60"
            wire:keydown.escape="closeViewModal"
        >
            <div
                class="absolute inset-0"
                wire:click="closeViewModal"
            ></div>

            <div
                class="relative z-50 w-full max-w-2xl mx-4 bg-slate-800 rounded-3xl p-8 shadow-2xl border border-slate-700"
            >
                <button
                    type="button"
                    wire:click="closeViewModal"
                    class="absolute top-6 right-6 text-slate-400 hover:text-slate-200 text-2xl leading-none"
                >
                    &times;
                </button>

                <div class="space-y-6">
                    {{-- Заголовок и статус --}}
                    <div>
                        <div class="flex items-start justify-between gap-4 mb-2">
                            <h2 class="text-2xl font-bold text-slate-100 {{ $viewingGoal->is_completed ? 'line-through text-slate-400' : '' }}">
                                {{ $viewingGoal->title }}
                            </h2>
                        </div>

                        @if($viewingGoal->author_name)
                            <p class="text-sm text-slate-400 italic">Автор: {{ $viewingGoal->author_name }}</p>
                        @endif

                        <div class="mt-2 flex items-center gap-2">
                            @if($viewingGoal->is_completed)
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/20 border border-emerald-400 text-emerald-200">
                                    ✓ Выполнено
                                </span>
                            @else
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-slate-600/50 border border-slate-500 text-slate-200">
                                    В процессе
                                </span>
                            @endif
                            <span class="px-3 py-1 rounded-full text-xs font-semibold whitespace-nowrap
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
                        </div>
                    </div>

                    {{-- Описание --}}
                    @if($viewingGoal->description)
                        <div>
                            <h3 class="text-sm font-semibold text-slate-300 mb-2">Описание</h3>
                            <p class="text-sm text-slate-300 leading-relaxed bg-slate-900/50 rounded-2xl p-4">
                                {{ nl2br($viewingGoal->description) }}
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
                                    <span class="text-sm text-slate-400">Отложено / Потрачено:</span>
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
    @endif
</div>
