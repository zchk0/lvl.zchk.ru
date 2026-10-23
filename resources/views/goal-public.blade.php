<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>{{ $goal->title }} &mdash; Публичная цель</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $goal->title }}">
    <link rel="icon" href="https://lvl.zchk.ru/favicon.svg" type="image/svg+xml">
    <link href="https://fonts.googleapis.com/css2?family=Geologica:wght@400;500;600&family=Unbounded:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-1: #0b0f1f;
            --bg-2: #0f1b2d;
            --card: rgba(18, 26, 42, 0.88);
            --text: #e5e7eb;
            --muted: #94a3b8;
            --accent: #f97316;
            --accent-2: #22d3ee;
            --success: #34d399;
            --border: rgba(148, 163, 184, 0.25);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Geologica", sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 15% 20%, rgba(34, 211, 238, 0.18), transparent 45%),
                radial-gradient(circle at 85% 10%, rgba(249, 115, 22, 0.22), transparent 40%),
                linear-gradient(160deg, var(--bg-1), var(--bg-2));
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 20px 48px;
            overflow-x: hidden;
        }

        body::before,
        body::after {
            content: "";
            position: fixed;
            width: 420px;
            height: 420px;
            border-radius: 50%;
            filter: blur(0px);
            opacity: 0.35;
            z-index: 0;
            animation: float 14s ease-in-out infinite;
        }

        body::before {
            background: radial-gradient(circle, rgba(249, 115, 22, 0.5), transparent 70%);
            top: -120px;
            left: -120px;
        }

        body::after {
            background: radial-gradient(circle, rgba(34, 211, 238, 0.45), transparent 70%);
            bottom: -160px;
            right: -140px;
            animation-delay: 2s;
        }

        .page {
            position: relative;
            z-index: 1;
            width: min(960px, 100%);
            display: grid;
            gap: 22px;
        }

        .hero {
            padding: 34px 36px 28px;
            border-radius: 28px;
            background: var(--card);
            border: 1px solid var(--border);
            box-shadow: 0 25px 60px rgba(6, 10, 18, 0.45);
            backdrop-filter: blur(12px);
            animation: rise 0.6s ease-out both;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 12px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            background: rgba(249, 115, 22, 0.15);
            border: 1px solid rgba(249, 115, 22, 0.35);
            color: #fed7aa;
        }

        h1 {
            font-family: "Unbounded", sans-serif;
            font-size: clamp(26px, 4vw, 40px);
            line-height: 1.15;
            margin: 18px 0 12px;
        }

        .author {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            color: var(--muted);
            font-size: 14px;
            margin: 0 0 16px;
        }

        .chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .chip {
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            border: 1px solid transparent;
            background: rgba(148, 163, 184, 0.15);
            color: #e2e8f0;
        }

        .chip-done {
            background: rgba(52, 211, 153, 0.18);
            border-color: rgba(52, 211, 153, 0.4);
            color: #bbf7d0;
        }

        .chip-progress {
            background: rgba(148, 163, 184, 0.2);
            border-color: rgba(148, 163, 184, 0.35);
        }

        .chip-urgent {
            background: rgba(248, 113, 113, 0.18);
            border-color: rgba(248, 113, 113, 0.4);
            color: #fecaca;
        }

        .chip-normal {
            background: rgba(251, 191, 36, 0.18);
            border-color: rgba(251, 191, 36, 0.4);
            color: #fde68a;
        }

        .chip-low {
            background: rgba(34, 197, 94, 0.16);
            border-color: rgba(34, 197, 94, 0.4);
            color: #bbf7d0;
        }

        .chip-archive {
            background: rgba(148, 163, 184, 0.18);
            border-color: rgba(148, 163, 184, 0.35);
            color: #e2e8f0;
        }

        .panel {
            padding: 26px 30px;
            border-radius: 24px;
            background: rgba(12, 18, 30, 0.7);
            border: 1px solid rgba(148, 163, 184, 0.2);
            box-shadow: 0 18px 40px rgba(8, 12, 22, 0.35);
            backdrop-filter: blur(8px);
            animation: rise 0.6s ease-out both;
        }

        .panel:nth-of-type(2) {
            animation-delay: 0.08s;
        }

        .panel:nth-of-type(3) {
            animation-delay: 0.16s;
        }

        .panel:nth-of-type(4) {
            animation-delay: 0.24s;
        }

        .panel h2 {
            margin: 0 0 14px;
            font-size: 16px;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            color: #cbd5f5;
        }

        .panel p {
            margin: 0;
            color: #d1d5db;
            line-height: 1.6;
        }

        .description-content {
            color: #d1d5db;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .description-content > :first-child {
            margin-top: 0;
        }

        .description-content > :last-child {
            margin-bottom: 0;
        }

        .description-content p,
        .description-content ul,
        .description-content ol,
        .description-content blockquote,
        .description-content pre {
            margin: 0 0 14px;
        }

        .description-content h1,
        .description-content h2,
        .description-content h3 {
            margin: 20px 0 10px;
            color: var(--text);
            letter-spacing: normal;
            line-height: 1.3;
            text-transform: none;
        }

        .description-content h1 {
            font-size: 24px;
        }

        .description-content h2 {
            font-size: 20px;
        }

        .description-content h3 {
            font-size: 17px;
        }

        .description-content ul,
        .description-content ol {
            padding-left: 24px;
        }

        .description-content a {
            color: var(--accent-2);
            text-underline-offset: 3px;
        }

        .description-content blockquote {
            padding-left: 14px;
            border-left: 3px solid rgba(34, 211, 238, 0.45);
            color: var(--muted);
        }

        .description-content code {
            padding: 2px 5px;
            border-radius: 6px;
            background: rgba(2, 6, 23, 0.8);
            font-size: 0.9em;
        }

        .description-content pre {
            padding: 14px;
            overflow-x: auto;
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 14px;
            background: rgba(2, 6, 23, 0.8);
        }

        .description-content pre code {
            padding: 0;
            background: transparent;
        }

        .progress-header {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 12px;
        }

        .percent {
            font-size: 20px;
            font-weight: 600;
            color: var(--accent-2);
        }

        .progress-track {
            margin: 14px 0 18px;
            width: 100%;
            height: 12px;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(148, 163, 184, 0.25);
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            width: 0;
            background: linear-gradient(90deg, var(--accent-2), var(--success));
            border-radius: inherit;
            transition: width 0.6s ease;
        }

        .stats {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        }

        .stat {
            background: rgba(15, 23, 42, 0.55);
            border-radius: 18px;
            padding: 14px 16px;
            border: 1px solid rgba(148, 163, 184, 0.18);
        }

        .stat .label {
            display: block;
            font-size: 12px;
            color: var(--muted);
            margin-bottom: 6px;
        }

        .stat .value {
            font-size: 18px;
            font-weight: 600;
        }

        .link-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 18px;
            border-radius: 999px;
            border: 1px solid rgba(249, 115, 22, 0.5);
            color: #fed7aa;
            text-decoration: none;
            background: rgba(249, 115, 22, 0.15);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .link-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(249, 115, 22, 0.2);
        }

        .subgoals-list {
            margin: 0;
            padding: 0;
            list-style: none;
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, 0.2);
            overflow: hidden;
            background: rgba(15, 23, 42, 0.45);
        }

        .subgoals-item {
            display: grid;
            grid-template-columns: 20px 1fr;
            gap: 12px;
            align-items: center;
            padding: 10px 14px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.15);
        }

        .subgoals-item:last-child {
            border-bottom: 0;
        }

        .subgoals-marker {
            width: 20px;
            height: 20px;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, 0.45);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: transparent;
            background: transparent;
        }

        .subgoals-item.is-done .subgoals-marker {
            color: #ecfeff;
            background: rgba(52, 211, 153, 0.75);
            border-color: rgba(52, 211, 153, 0.85);
        }

        .subgoals-title {
            line-height: 1.4;
        }

        .subgoals-item.is-done .subgoals-title {
            color: #94a3b8;
            text-decoration: line-through;
        }

        .subgoals-item.is-collapsed {
            display: none;
        }

        .subgoals-toggle {
            width: 100%;
            border: 0;
            border-top: 1px solid rgba(148, 163, 184, 0.15);
            padding: 12px 14px;
            color: #cbd5e1;
            background: rgba(15, 23, 42, 0.35);
            font: inherit;
            font-size: 14px;
            cursor: pointer;
            transition: color 0.2s ease, background 0.2s ease;
        }

        .subgoals-toggle:hover {
            color: #f8fafc;
            background: rgba(15, 23, 42, 0.65);
        }

        .footer {
            text-align: center;
            color: var(--muted);
            font-size: 12px;
            padding-top: 6px;
        }

        @keyframes rise {
            from {
                opacity: 0;
                transform: translateY(16px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(18px);
            }
        }

        @media (max-width: 640px) {
            body {
                padding: 20px 16px 36px;
            }

            .hero,
            .panel {
                padding: 24px 22px;
            }

            .stats {
                grid-template-columns: 1fr;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            * {
                animation: none !important;
                transition: none !important;
            }
        }
    </style>
</head>
<body>
    @php
        $priorityClass = match ($goal->priority) {
            1 => 'chip-urgent',
            2 => 'chip-normal',
            3 => 'chip-low',
            4 => 'chip-archive',
            default => 'chip-normal',
        };
        $priorityLabel = match ($goal->priority) {
            1 => 'Высокий приоритет',
            2 => 'Средний приоритет',
            3 => 'Низкий приоритет',
            4 => 'Архив',
            default => 'Без приоритета',
        };
        $subgoals = collect($goal->subgoals ?? [])
            ->sortBy('position')
            ->values();
        $doneSubgoals = $subgoals->where('is_done', true)->count();
        $visibleSubgoalsLimit = 8;
        $hiddenSubgoalsCount = max(0, $subgoals->count() - $visibleSubgoalsLimit);
        $descriptionFormat = in_array($goal->description_format, \App\Models\Goal::DESCRIPTION_FORMATS, true)
            ? $goal->description_format
            : 'raw';
    @endphp

    <main class="page">
        <section class="hero">
            <span class="badge">Публичная карточка</span>
            <h1>{{ $goal->title }}</h1>
            @if($goal->author_tags)
                <div class="author">
                    <span>Теги:</span>
                    @foreach($goal->author_tags as $tag)
                        <span class="chip" style="overflow-wrap: anywhere;">{{ $tag }}</span>
                    @endforeach
                </div>
            @endif
            <div class="chips">
                <span class="chip {{ $goal->is_completed ? 'chip-done' : 'chip-progress' }}">
                    {{ $goal->is_completed ? 'Готово' : 'В процессе' }}
                </span>
                <span class="chip {{ $priorityClass }}">{{ $priorityLabel }}</span>
            </div>
        </section>

        @if($goal->description)
            <section class="panel">
                <h2>Описание</h2>
                <div class="description-content">
                    @if($descriptionFormat === 'markdown')
                        {!! Illuminate\Support\Str::markdown($goal->description, [
                            'renderer' => ['soft_break' => "<br>\n"],
                        ]) !!}
                    @elseif($descriptionFormat === 'html')
                        {!! $goal->description !!}
                    @else
                        {!! nl2br(e($goal->description)) !!}
                    @endif
                </div>
            </section>
        @endif

        @if($subgoals->isNotEmpty())
            <section class="panel">
                <div class="progress-header">
                    <h2>Подзадачи</h2>
                    <span class="percent">{{ $doneSubgoals }}/{{ $subgoals->count() }}</span>
                </div>
                <ul class="subgoals-list">
                    @foreach($subgoals as $subgoal)
                        @php
                            $isDone = (bool) ($subgoal['is_done'] ?? false);
                            $title = (string) ($subgoal['title'] ?? 'Подзадача');
                            $isInitiallyHidden = $loop->index >= $visibleSubgoalsLimit;
                        @endphp
                        <li class="subgoals-item {{ $isDone ? 'is-done' : '' }} {{ $isInitiallyHidden ? 'is-collapsed' : '' }}" @if($isInitiallyHidden) data-subgoal-collapsed @endif>
                            <span class="subgoals-marker" aria-hidden="true">{{ $isDone ? '✓' : '' }}</span>
                            <span class="subgoals-title">{{ $title }}</span>
                        </li>
                    @endforeach
                    @if($hiddenSubgoalsCount > 0)
                        <li>
                            <button
                                type="button"
                                class="subgoals-toggle"
                                data-subgoals-toggle
                                data-show-label="Показать ещё {{ $hiddenSubgoalsCount }}"
                                data-hide-label="Свернуть">
                                Показать ещё {{ $hiddenSubgoalsCount }}
                            </button>
                        </li>
                    @endif
                </ul>
            </section>
        @endif

        @if($goal->target_amount > 0)
            <section class="panel">
                <div class="progress-header">
                    <h2>Прогресс</h2>
                    <span class="percent">{{ $goal->progress_percent }}%</span>
                </div>
                <div class="progress-track">
                    <div class="progress-bar" style="width: {{ $goal->progress_percent }}%;"></div>
                </div>
                <div class="stats">
                    <div class="stat">
                        <span class="label">Цель</span>
                        <span class="value">{{ number_format($goal->target_amount, 0, ',', ' ') }} &#8381;</span>
                    </div>
                    <div class="stat">
                        <span class="label">Достигнуто</span>
                        <span class="value">{{ number_format($goal->saved_amount, 0, ',', ' ') }} &#8381;</span>
                    </div>
                    <div class="stat">
                        <span class="label">Осталось</span>
                        <span class="value">{{ number_format($goal->target_amount - $goal->saved_amount, 0, ',', ' ') }} &#8381;</span>
                    </div>
                </div>
            </section>
        @endif

        @if($goal->url)
            <section class="panel">
                <h2>Ссылка</h2>
                <a class="link-button" href="{{ $goal->url }}" target="_blank" rel="noopener noreferrer">
                    Открыть источник
                    <span aria-hidden="true">&#8599;</span>
                </a>
            </section>
        @endif

        <div class="footer">Карточка доступна по этой ссылке, пока она публичная.</div>
    </main>
    <script>
        document.querySelectorAll('[data-subgoals-toggle]').forEach((button) => {
            const list = button.closest('.subgoals-list');
            if (!list) {
                return;
            }

            const collapsedItems = list.querySelectorAll('[data-subgoal-collapsed]');
            button.addEventListener('click', () => {
                const shouldExpand = button.getAttribute('aria-expanded') !== 'true';
                collapsedItems.forEach((item) => item.classList.toggle('is-collapsed', !shouldExpand));
                button.setAttribute('aria-expanded', shouldExpand ? 'true' : 'false');
                button.textContent = shouldExpand ? button.dataset.hideLabel : button.dataset.showLabel;
            });
        });
    </script>
</body>
</html>
