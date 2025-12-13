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

    <div class="max-w-6xl mx-auto px-4">
        {{-- Заголовок + селект фильтра --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <h1 class="text-2xl sm:text-3xl font-semibold">
                Цели v0.24
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

        @php
            $colsClass = $visiblePriority === 'all' ? 'md:grid-cols-3' : 'md:grid-cols-1';
        @endphp

        {{-- КОЛОНКИ (или одна, если выбран фильтр) --}}
        <div class="grid gap-6 {{ $colsClass }}">
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
</div>
