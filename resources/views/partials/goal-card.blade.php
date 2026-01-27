@php
$dragEnabled = $dragEnabled ?? true;
@endphp
<div
    class="rounded-2xl {{ $goal->is_completed ? 'opacity-65' : '' }} bg-slate-900/70 border border-slate-700 px-3 py-3 text-sm flex flex-col gap-2 {{ $dragEnabled ? 'cursor-grab active:cursor-grabbing' : '' }}"
    wire:key="goal-{{ $goal->id }}"
    data-goal-card
    data-goal-id="{{ $goal->id }}"
    draggable="{{ $dragEnabled ? 'true' : 'false' }}">
    <div class="flex items-start justify-between gap-2">
        <div>
            <button
                wire:click="openView({{ $goal->id }})"
                class="font-semibold text-slate-100 text-base {{ $goal->is_completed ? 'line-through text-slate-400' : '' }} text-left hover:text-indigo-300 transition cursor-pointer">
                {{ $goal->title }}
            </button>
            @if($goal->author_name)
            <div class="text-xs text-slate-400 italic">
                {{ $goal->author_name }}
            </div>
            @endif
        </div>
        <div class="flex flex-col items-end gap-2">
            <button
                wire:click="toggleCompleted({{ $goal->id }})"
                class="text-xs px-2 py-1 rounded-xl border
                {{ $goal->is_completed ? 'bg-emerald-500/20 border-emerald-400 text-emerald-200' : 'bg-slate-800 border-slate-600 text-slate-300' }}">
                {{ $goal->is_completed ? 'Готово' : 'Завершить' }}
            </button>
        </div>
    </div>

    <div class="text-xs text-slate-400 flex justify-between">
        @if(!empty($goal->target_amount && !$goal->is_completed))<span>Цель: {{ number_format($goal->target_amount, 0, ',', ' ') }} ₽</span>@endif
        @if(!empty($goal->saved_amount))<span>Достигнуто: {{ number_format($goal->saved_amount, 0, ',', ' ') }} ₽</span>@endif
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

                <div class="flex items-center gap-1 sm:hidden">
                    <button
                        type="button"
                        wire:click="moveWithinPriority({{ $goal->id }}, 'up')"
                        class="px-2 py-1 rounded-lg text-xs bg-slate-800 border border-slate-600 text-slate-300 hover:text-slate-100 hover:border-slate-500 disabled:opacity-40 disabled:cursor-not-allowed"
                        title="Move up"
                        aria-label="Move up"
                        {{ $dragEnabled ? '' : 'disabled' }}>
                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                        </svg>
                    </button>
                    <button
                        type="button"
                        wire:click="moveWithinPriority({{ $goal->id }}, 'down')"
                        class="px-2 py-1 rounded-lg text-xs bg-slate-800 border border-slate-600 text-slate-300 hover:text-slate-100 hover:border-slate-500 disabled:opacity-40 disabled:cursor-not-allowed"
                        title="Move down"
                        aria-label="Move down"
                        {{ $dragEnabled ? '' : 'disabled' }}>
                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>

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
        <button
            type="button"
            wire:click="togglePublic({{ $goal->id }})"
            aria-pressed="{{ $goal->is_public ? 'true' : 'false' }}"
            class="absolute top-[2px] left-[98px] text-[10px] px-2 py-2 rounded-full border inline-flex items-center gap-1
                    {{ $goal->is_public ? 'bg-emerald-500/20 border-emerald-400 text-emerald-200' : 'bg-slate-800 border-slate-600 text-slate-300' }}">
            <span class="w-2 h-2 rounded-full {{ $goal->is_public ? 'bg-emerald-300' : 'bg-slate-500' }}"></span>
        </button>

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
            <span>Ссылка</span>
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