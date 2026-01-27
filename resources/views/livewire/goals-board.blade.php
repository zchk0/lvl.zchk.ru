<div class="min-h-screen bg-slate-900 text-slate-100 py-10">
    {{-- Глобальный индикатор загрузки Livewire --}}
    <div class="fixed inset-x-0 top-4 z-40 flex justify-center pointer-events-none opacity-0" wire:loading.delay.class="opacity-100">
        <div class="px-4 py-2 rounded-full bg-slate-800/95 border border-slate-700 shadow-lg flex items-center gap-2 text-xs text-slate-200">
            <span class="w-3 h-3 rounded-full border-2 border-indigo-400 border-t-transparent animate-spin"></span>
            <span>Синхронизация изменений…</span>
        </div>
    </div>

    @php
        $isAll = $visiblePriority === 'all';
        $colsClass = $isAll ? 'md:grid-cols-3' : 'md:grid-cols-1';
        $widthClass = $isAll ? 'max-w-6xl' : 'max-w-3xl mx-auto';
        $isSearch = mb_strlen(trim($search)) > 0;
        $dragEnabled = ! $isSearch;
    @endphp

    <div class="max-w-6xl mx-auto px-4" data-goals-board data-drag-enabled="{{ $dragEnabled ? '1' : '0' }}">
        {{-- Заголовок + селект фильтра --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-7">
            <h1 class="text-2xl sm:text-3xl font-semibold">
                Цели v0.37
            </h1>

            <div class="flex flex-col sm:flex-row sm:items-center gap-3 text-sm w-full sm:w-auto">
                {{-- Поиск --}}
                <div class="relative w-full sm:w-72">
                    <input
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Поиск по целям…"
                        class="w-full rounded-2xl bg-slate-800 border border-slate-600 pl-3 pr-10 py-2 text-sm text-slate-100 outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500
                        [&::-webkit-search-cancel-button]:hidden [&::-webkit-search-decoration]:hidden" />
                    @if(trim($search) !== '')
                        <button
                            type="button"
                            wire:click="$set('search','')"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-200"
                            title="Очистить">
                            ✕
                        </button>
                    @endif
                </div>
                {{-- Фильтр приоритета --}}
                <div class="flex items-center gap-2">
                    <select
                        wire:model.live="visiblePriority"
                        class="rounded-2xl bg-slate-800 border border-slate-600 px-3 py-2 text-sm text-slate-100 outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        <option value="all">Все приоритеты</option>
                        <option value="1">Только высокий</option>
                        <option value="2">Только средний</option>
                        <option value="3">Только низкий</option>
                        <option value="4">Архив</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- КОЛОНКИ (или одна, если выбран фильтр) --}}
        <div class="grid gap-6 {{ "$colsClass $widthClass" }}">
            {{-- Высокий приоритет --}}
            @if(($visiblePriority === 'all' || $visiblePriority === '1') && !($isSearch && $highGoals->isEmpty()))
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
                            class="px-3 py-1 rounded-2xl text-xs font-semibold bg-red-500 hover:bg-red-600 text-white disabled:opacity-60">
                            + Добавить
                        </button>
                    </div>

                    @if($highGoals->isNotEmpty())
                        @php
                            $totalTarget = $highGoals->sum('target_amount');
                            $totalSaved = $highGoals->sum('saved_amount');
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

                    <div class="flex flex-col gap-3" data-goals-column data-priority="1">
                        @forelse($highGoals as $goal)
                            @include('partials.goal-card', ['goal' => $goal, 'dragEnabled' => $dragEnabled])
                            @empty
                            <p class="text-xs text-slate-500">
                                Пока ничего нет. Добавь первую цель.
                            </p>
                        @endforelse
                    </div>
                </div>
            @endif

            {{-- Средний приоритет --}}
            @if(($visiblePriority === 'all' || $visiblePriority === '2') && !($isSearch && $mediumGoals->isEmpty()))
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
                            class="px-3 py-1 rounded-2xl text-xs font-semibold bg-amber-400 hover:bg-amber-500 text-slate-900 disabled:opacity-60">
                            + Добавить
                        </button>
                    </div>

                    @if($mediumGoals->isNotEmpty())
                        @php
                            $totalTarget = $mediumGoals->sum('target_amount');
                            $totalSaved = $mediumGoals->sum('saved_amount');
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

                    <div class="flex flex-col gap-3" data-goals-column data-priority="2">
                        @forelse($mediumGoals as $goal)
                            @include('partials.goal-card', ['goal' => $goal, 'dragEnabled' => $dragEnabled])
                            @empty
                            <p class="text-xs text-slate-500">
                                Пока пусто.
                            </p>
                        @endforelse
                    </div>
                </div>
            @endif

            {{-- Низкий приоритет --}}
            @if(($visiblePriority === 'all' || $visiblePriority === '3') && !($isSearch && $lowGoals->isEmpty()))
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
                            class="px-3 py-1 rounded-2xl text-xs font-semibold bg-emerald-400 hover:bg-emerald-500 text-slate-900 disabled:opacity-60">
                            + Добавить
                        </button>
                    </div>

                    @if($lowGoals->isNotEmpty())
                        @php
                            $totalTarget = $lowGoals->sum('target_amount');
                            $totalSaved = $lowGoals->sum('saved_amount');
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

                    <div class="flex flex-col gap-3" data-goals-column data-priority="3">
                        @forelse($lowGoals as $goal)
                            @include('partials.goal-card', ['goal' => $goal, 'dragEnabled' => $dragEnabled])
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
                            class="px-3 py-1 rounded-2xl text-xs font-semibold bg-slate-400 hover:bg-slate-500 text-slate-900 disabled:opacity-60">
                            + Добавить
                        </button>
                    </div>

                    @if($archive->isNotEmpty())
                        @php
                            $totalTarget = $archive->sum('target_amount');
                            $totalSaved = $archive->sum('saved_amount');
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

                    <div class="flex flex-col gap-3" data-goals-column data-priority="4">
                        @forelse($archive as $goal)
                            @include('partials.goal-card', ['goal' => $goal, 'dragEnabled' => $dragEnabled])
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
        @include('partials.goal-modal-add-edit')
    @endif

    {{-- МОДАЛКА ПРОСМОТРА --}}
    @if($showViewModal && $viewingGoal)
        @include('partials.goal-modal-view')
    @endif
</div>
