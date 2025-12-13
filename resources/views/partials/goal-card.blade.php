<div class="rounded-2xl bg-slate-900/70 border border-slate-700 px-3 py-3 text-sm flex flex-col gap-2">
    <div class="flex items-start justify-between gap-2">
        <div>
            <div class="font-semibold text-slate-100 text-base {{ $goal->is_completed ? 'line-through text-slate-400' : '' }}">
                {{ $goal->title }}
            </div>
            @if($goal->author_name)
            <div class="text-xs text-slate-400 italic">
                {{ $goal->author_name }}
            </div>
            @endif
        </div>
        <button
            wire:click="toggleCompleted({{ $goal->id }})"
            class="text-xs px-2 py-1 rounded-xl border
                {{ $goal->is_completed ? 'bg-emerald-500/20 border-emerald-400 text-emerald-200' : 'bg-slate-800 border-slate-600 text-slate-300' }}">
            {{ $goal->is_completed ? 'Готово' : 'Завершить' }}
        </button>
    </div>

    <div class="text-xs text-slate-400 flex justify-between">
        @if(!empty($goal->target_amount))<span>Цель: {{ number_format($goal->target_amount, 0, ',', ' ') }} ₽</span>@endif
        <span>Отложено: {{ number_format($goal->saved_amount, 0, ',', ' ') }} ₽</span>
    </div>

    @if(!empty($goal->target_amount))
    <div class="mt-1">
        <div class="w-full h-2 rounded-full bg-slate-800 overflow-hidden">
            <div
                class="h-full bg-emerald-400 transition-all"
                style="width: {{ $goal->progress_percent }}%;"></div>
        </div>
        <div class="text-xs text-slate-400 mt-1 text-right">
            {{ $goal->progress_percent }}%
        </div>
    </div>
    @endif

    {{-- ВЫПАДАЮЩИЙ БЛОК ДЕЙСТВИЙ + КНОПКА ССЫЛКИ --}}
    <div class="relative mb-1 pr-30">
        <details class="mb-0">
            <summary
                class="cursor-pointer select-none text-xs text-slate-300
                    flex items-center gap-1 px-2 py-1.5
                    rounded-xl bg-slate-800 border border-slate-600 w-fit">
                <span>Настройки</span>
                <span class="text-slate-500 text-[10px]">▼</span>
            </summary>

            <div class="flex flex-wrap gap-2 items-center mt-4">
                <button
                    wire:click="openEdit({{ $goal->id }})"
                    class="px-2 py-1 rounded-xl bg-slate-800 text-xs text-slate-100 border border-slate-600">
                    Редактировать
                </button>
                <button
                    wire:click="delete({{ $goal->id }})"
                    class="px-2 py-1 rounded-xl bg-red-500/20 border border-red-500 text-red-300 text-xs">
                    Удалить
                </button>

                <div class="flex flex-wrap items-center gap-2 text-xs text-slate-400 pl-1 w-full">
                    <span class="w-full py-1">Переместить в:</span>
                    @if($goal->priority !== 1)
                    <button
                        wire:click="moveToPriority({{ $goal->id }}, 1)"
                        class="px-2 py-1 rounded-xl bg-slate-800 border border-red-400 text-red-300">
                        Высокий
                    </button>
                    @endif
                    @if($goal->priority !== 2)
                    <button
                        wire:click="moveToPriority({{ $goal->id }}, 2)"
                        class="px-2 py-1 rounded-xl bg-slate-800 border border-amber-300 text-amber-200">
                        Средний
                    </button>
                    @endif
                    @if($goal->priority !== 3)
                    <button
                        wire:click="moveToPriority({{ $goal->id }}, 3)"
                        class="px-2 py-1 rounded-xl bg-slate-800 border border-emerald-300 text-emerald-200">
                        Низкий
                    </button>
                    @endif
                    @if($goal->priority !== 4)
                    <button
                        wire:click="moveToPriority({{ $goal->id }}, 4)"
                        class="px-2 py-1 rounded-xl bg-slate-800 border border-slate-300 text-slate-200">
                        Архив
                    </button>
                    @endif
                </div>
            </div>
        </details>

        @if(!empty($goal->url))
        <a
            href="{{ $goal->url }}"
            target="_blank"
            rel="noopener noreferrer"
            class="absolute top-0 right-0
                    inline-flex items-center gap-1
                    px-3 py-1.5 rounded-xl text-xs font-medium
                    bg-indigo-500/15 border border-indigo-400/70 text-indigo-100
                    hover:bg-indigo-500/30 hover:border-indigo-300
                    focus:outline-none focus:ring-2 focus:ring-indigo-400/60
                    transition">
            <span>Открыть ссылку</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M14 3h7m0 0v7m0-7L10 14" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M5 5v14h14" />
            </svg>
        </a>
        @endif
    </div>
</div>