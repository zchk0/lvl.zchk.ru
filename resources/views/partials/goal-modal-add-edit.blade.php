<div
    data-modal-open="true"
    class="fixed inset-0 z-40 overflow-y-auto overscroll-contain"
    wire:keydown.escape="closeModal">
    {{-- затемнение + клик по фону --}}
    <div
        class="fixed inset-0 bg-black/60"
        wire:click="closeModal"></div>

    {{-- центрирование + отступы (контейнер можно скроллить при необходимости) --}}
    <div class="relative flex min-h-[100dvh] items-center justify-center p-4">
        <div
            class="relative z-50 w-full max-w-lg bg-slate-800 rounded-3xl p-6 shadow-2xl border border-slate-700 overflow-y-auto overscroll-contain"
            style="max-height: calc(100dvh - 2rem - env(safe-area-inset-bottom)); -webkit-overflow-scrolling: touch;">
            <div class="flex items-start justify-between mb-4">
                <h2 class="text-lg font-semibold">
                    {{ $editingId ? 'Редактирование цели' : 'Новая цель' }}
                </h2>
                <button
                    type="button"
                    wire:click="closeModal"
                    class="text-slate-400 hover:text-slate-200 text-xl leading-none">
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
                                   @error('title') border-red-500 focus:ring-red-500 @else border-slate-600 focus:border-indigo-500 focus:ring-indigo-500 @enderror">
                    @error('title')
                    <div class="text-xs text-red-400 mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm mb-1">Описание</label>
                    <textarea
                        placeholder="Подробное описание цели..."
                        rows="4"
                        wire:model.defer="description"
                        class="w-full rounded-2xl bg-slate-900 border border-slate-600 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 resize-y"></textarea>
                </div>

                <div>
                    <label class="block text-sm mb-1">URL</label>
                    <input
                        placeholder="Может на ВБ, а может и на сайт с чем-то связанным..."
                        type="text"
                        wire:model.defer="url"
                        class="w-full rounded-2xl bg-slate-900 border border-slate-600 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm mb-1">Стоимость цели</label>
                        <input
                            type="number"
                            step="1"
                            min="0"
                            wire:model.defer="target_amount"
                            class="w-full rounded-2xl bg-slate-900 border px-3 py-2 text-sm outline-none focus:ring-1
                                       @error('target_amount') border-red-500 focus:ring-red-500 @else border-slate-600 focus:border-indigo-500 focus:ring-indigo-500 @enderror">
                        @error('target_amount')
                        <div class="text-xs text-red-400 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Достигнуто</label>
                        <input
                            placeholder="Или потрачено"
                            type="number"
                            step="0.1"
                            min="0"
                            wire:model.defer="saved_amount"
                            class="w-full rounded-2xl bg-slate-900 border px-3 py-2 text-sm outline-none focus:ring-1
                                       @error('saved_amount') border-red-500 focus:ring-red-500 @else border-slate-600 focus:border-indigo-500 focus:ring-indigo-500 @enderror">
                        @error('saved_amount')
                        <div class="text-xs text-red-400 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div
                    x-data="{
                        target: $wire.entangle('target_amount'),
                        value:  $wire.entangle('saved_amount'),
                        get t() { return Math.max(0, parseInt(this.target || 0)); },
                        get v() { return Math.max(0, parseInt(this.value  || 0)); },
                        get pct() {
                            if (!this.t) return 0;
                            return Math.min(100, Math.max(0, (this.v / this.t) * 100));
                        },
                        clamp() {
                            let v = parseInt(this.value ?? 0);
                            if (isNaN(v)) v = 0;
                            if (v < 0) v = 0;
                            if (this.t > 0 && v > this.t) v = this.t;
                            this.value = v;
                        }
                    }"
                    x-init="clamp()"
                    x-cloak
                    x-show="t > 0"
                    x-transition.opacity.duration.150ms
                    class="mt-2 rounded-2xl border border-slate-700 bg-slate-900/60 p-4">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-sm font-medium">Прогресс</div>
                        <div class="text-xs text-slate-300">
                            <span class="font-semibold" x-text="v"></span>
                            <span class="text-slate-500"> / </span>
                            <span x-text="t"></span>
                            <span class="text-slate-500 ml-2" x-text="`(${pct.toFixed(0)}%)`"></span>
                        </div>
                    </div>
                    <input
                        type="range"
                        class="goal-range w-full"
                        min="0"
                        :max="t"
                        step="1"
                        x-model.number="value"
                        @input="clamp()"
                        :style="`--pct:${pct}%`">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm mb-1">Приоритет</label>
                        <select
                            wire:model.defer="priority"
                            class="w-full rounded-2xl bg-slate-900 border border-slate-600 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
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
                            class="rounded border-slate-600 bg-slate-900">
                        <label for="is_completed" class="text-sm">Выполнено</label>
                    </div>
                </div>

                <div>
                    <label class="block text-sm mb-1">Автор</label>
                    <input
                        placeholder="Кто?"
                        type="text"
                        wire:model.defer="author_name"
                        class="w-full rounded-2xl bg-slate-900 border border-slate-600 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>

                <div class="flex gap-3 mt-4 justify-end">
                    <button
                        type="button"
                        wire:click="closeModal"
                        wire:loading.attr="disabled"
                        class="px-4 py-2 rounded-2xl bg-slate-700 text-sm disabled:opacity-60">
                        Отменить
                    </button>

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="save"
                        class="px-4 py-2 rounded-2xl bg-indigo-500 hover:bg-indigo-600 text-sm font-semibold text-white flex items-center gap-2 disabled:opacity-60">
                        <span wire:loading.remove wire:target="save">
                            {{ $editingId ? 'Сохранить' : 'Создать' }}
                        </span>

                        <span
                            wire:loading.inline
                            wire:target="save"
                            class="flex items-center gap-2">
                            <span class="w-4 h-4 rounded-full border-2 border-white border-t-transparent animate-spin"></span>
                            <span>Сохраняем…</span>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>