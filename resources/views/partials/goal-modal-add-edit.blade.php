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
            class="relative z-50 w-full max-w-xl bg-slate-800 rounded-3xl p-6 shadow-2xl border border-slate-700 overflow-y-auto overscroll-contain"
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

                <div
                    x-data="{
                        descriptionFormat: $wire.entangle('description_format'),
                        descriptionFullscreen: false,
                        applyDescriptionFormat(command) {
                            if (this.descriptionFormat === 'raw') return;

                            const editor = this.$refs.descriptionEditor;
                            const start = editor.selectionStart;
                            const end = editor.selectionEnd;
                            const selected = editor.value.slice(start, end);
                            let replacement = selected;

                            if (this.descriptionFormat === 'markdown') {
                                const inline = {
                                    bold: ['**', '**', 'жирный текст'],
                                    italic: ['_', '_', 'курсив'],
                                };

                                if (inline[command]) {
                                    const [before, after, placeholder] = inline[command];
                                    replacement = before + (selected || placeholder) + after;
                                } else if (command === 'heading') {
                                    replacement = '## ' + (selected || 'Заголовок');
                                } else if (command === 'link') {
                                    replacement = '[' + (selected || 'текст ссылки') + '](https://)';
                                } else if (command === 'bulletList') {
                                    replacement = (selected || 'элемент списка')
                                        .split('\n').map(line => '- ' + line).join('\n');
                                } else if (command === 'numberedList') {
                                    replacement = (selected || 'элемент списка')
                                        .split('\n').map((line, index) => (index + 1) + '. ' + line).join('\n');
                                } else if (command === 'quote') {
                                    replacement = (selected || 'цитата')
                                        .split('\n').map(line => '> ' + line).join('\n');
                                } else if (command === 'code') {
                                    replacement = '```\n' + (selected || 'код') + '\n```';
                                }
                            }

                            if (this.descriptionFormat === 'html') {
                                const inline = {
                                    bold: ['<strong>', '</strong>', 'жирный текст'],
                                    italic: ['<em>', '</em>', 'курсив'],
                                    code: ['<code>', '</code>', 'код'],
                                };

                                if (inline[command]) {
                                    const [before, after, placeholder] = inline[command];
                                    replacement = before + (selected || placeholder) + after;
                                } else if (command === 'heading') {
                                    replacement = '<h2>' + (selected || 'Заголовок') + '</h2>';
                                } else if (command === 'link') {
                                    replacement = '<a href=&quot;https://&quot;>' + (selected || 'текст ссылки') + '</a>';
                                } else if (command === 'bulletList') {
                                    replacement = '<ul>\n' + (selected || 'элемент списка')
                                        .split('\n').map(line => '  <li>' + line + '</li>').join('\n') + '\n</ul>';
                                } else if (command === 'numberedList') {
                                    replacement = '<ol>\n' + (selected || 'элемент списка')
                                        .split('\n').map(line => '  <li>' + line + '</li>').join('\n') + '\n</ol>';
                                } else if (command === 'quote') {
                                    replacement = '<blockquote>' + (selected || 'цитата') + '</blockquote>';
                                }
                            }

                            editor.setRangeText(replacement, start, end, 'end');
                            editor.dispatchEvent(new Event('input', { bubbles: true }));
                            editor.focus();
                        },
                    }"
                    x-on:keydown.escape="
                        if (descriptionFullscreen) {
                            $event.preventDefault();
                            $event.stopPropagation();
                            descriptionFullscreen = false;
                        }
                    "
                    x-bind:class="descriptionFullscreen
                        ? 'fixed inset-0 z-[60] !m-0 flex flex-col overflow-hidden bg-slate-950 p-6 sm:p-8 lg:p-10'
                        : ''">
                    <div
                        x-bind:class="descriptionFullscreen
                            ? 'mb-4 rounded-2xl border border-slate-700 bg-slate-800/90 p-3 shadow-lg shadow-black/20'
                            : 'mb-2'"
                        class="flex flex-wrap items-center justify-between gap-3">
                        <label for="goal-description" class="block text-sm">Описание</label>

                        <div class="flex flex-wrap items-center gap-2">
                            <div class="inline-flex rounded-xl border border-slate-600 bg-slate-900/50 p-1" role="tablist" aria-label="Формат редактора">
                                @foreach(['raw' => 'Raw', 'markdown' => 'Markdown', 'html' => 'HTML'] as $format => $label)
                                    <button
                                        type="button"
                                        role="tab"
                                        x-on:click="descriptionFormat = '{{ $format }}'"
                                        x-bind:aria-selected="descriptionFormat === '{{ $format }}'"
                                        x-bind:class="descriptionFormat === '{{ $format }}'
                                            ? 'bg-indigo-500/25 text-indigo-100 shadow-sm'
                                            : 'text-slate-400 hover:bg-slate-700/60 hover:text-slate-200'"
                                        class="rounded-lg px-3 py-1.5 text-xs font-medium transition">
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>

                            <button
                                type="button"
                                x-on:click="descriptionFullscreen = !descriptionFullscreen; $nextTick(() => $refs.descriptionEditor.focus())"
                                x-bind:aria-pressed="descriptionFullscreen"
                                x-bind:aria-label="descriptionFullscreen ? 'Свернуть редактор' : 'Развернуть редактор на весь экран'"
                                x-bind:title="descriptionFullscreen ? 'Свернуть редактор' : 'Развернуть редактор на весь экран'"
                                class="inline-flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-xl border border-slate-600 bg-slate-900/50 p-0 text-slate-300 hover:border-slate-500 hover:bg-slate-700/60 hover:text-white transition">
                                <span aria-hidden="true" class="text-base leading-none">⛶</span>
                            </button>
                        </div>
                    </div>

                    <div
                        x-show="descriptionFormat !== 'raw'"
                        style="display: none;"
                        x-bind:class="descriptionFullscreen
                            ? 'mb-4 border-slate-600 bg-slate-800/80 p-2.5 shadow-lg shadow-black/20'
                            : 'mb-2 border-slate-700 bg-slate-900/40 p-1.5'"
                        class="flex flex-wrap gap-1.5 rounded-xl border"
                        aria-label="Форматирование текста">
                        @foreach([
                            'heading' => ['H2', 'Заголовок'],
                            'bold' => ['B', 'Жирный текст'],
                            'italic' => ['I', 'Курсив'],
                            'link' => ['Ссылка', 'Ссылка'],
                            'bulletList' => ['• Список', 'Маркированный список'],
                            'numberedList' => ['1. Список', 'Нумерованный список'],
                            'quote' => ['❝', 'Цитата'],
                            'code' => ['</>', 'Код'],
                        ] as $command => [$label, $title])
                            <button
                                type="button"
                                x-on:click="applyDescriptionFormat('{{ $command }}')"
                                title="{{ $title }}"
                                class="min-w-8 rounded-lg border border-slate-600/80 bg-slate-900/35 px-2.5 py-1.5 text-xs font-medium text-slate-300 shadow-sm hover:border-slate-500 hover:bg-slate-700 hover:text-white transition">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>

                    <textarea
                        id="goal-description"
                        x-ref="descriptionEditor"
                        x-bind:placeholder="descriptionFormat === 'markdown'
                            ? 'Описание в формате Markdown...'
                            : (descriptionFormat === 'html' ? 'Описание в формате HTML...' : 'Подробное описание цели...')"
                        x-bind:class="[
                            descriptionFullscreen
                                ? 'flex-1 min-h-0 !resize-none !border-slate-600 !bg-slate-900 !px-5 !py-4 !text-[14.5px] shadow-2xl shadow-black/30'
                                : '',
                        ]"
                        rows="7"
                        wire:model.defer="description"
                        class="w-full rounded-2xl bg-slate-900 border border-slate-600 px-3 py-2 text-[13.5px] font-mono outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 resize-y"></textarea>

                    <p
                        x-bind:class="descriptionFullscreen ? 'mt-3 px-2' : 'mt-1.5'"
                        class="text-xs text-slate-500">
                        <span x-show="descriptionFormat === 'raw'">Обычный текст без разметки.</span>
                        <span x-show="descriptionFormat === 'markdown'" style="display: none;">Панель вставляет Markdown-разметку в выделенный текст.</span>
                        <span x-show="descriptionFormat === 'html'" style="display: none;">Панель вставляет HTML-теги в выделенный текст.</span>
                    </p>
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
                        get t() { return Math.max(0, parseFloat(this.target || 0)); },
                        get v() { return Math.max(0, parseFloat(this.value  || 0)); },
                        get maxRange() { return Math.max(this.t, this.v, 1); },
                        get pct() {
                            if (!this.t) return 0;
                            return Math.min(100, Math.max(0, (this.v / this.t) * 100));
                        },
                        clamp() {
                            let v = parseFloat(this.value ?? 0);
                            if (isNaN(v)) v = 0;
                            if (v < 0) v = 0;
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
                        :max="maxRange"
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
