@php
    $endpoint = route('api.goals.store');
    $updateEndpoint = route('api.goals.update', ['goal' => 42]);
    $sections = [
        'quickstart' => 'Быстрый старт',
        'authentication' => 'Авторизация',
        'create-goal' => 'Создание цели',
        'update-goal' => 'Изменение цели',
        'parameters' => 'Параметры',
        'response' => 'Успешный ответ',
        'errors' => 'Ошибки',
    ];
    $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    $minimalRequest = ['title' => 'Купить монитор'];
    $fullRequest = [
        'title' => 'Купить монитор',
        'description' => '**Для работы** и учёбы',
        'description_format' => 'markdown',
        'url' => 'https://example.com/monitor',
        'tags' => ['IVAN', 'Работа'],
        'target_amount' => 30000,
        'saved_amount' => 5000,
        'priority' => 2,
        'is_completed' => false,
    ];
    $updateRequest = [
        'title' => 'Купить монитор для работы',
        'saved_amount' => 15000,
        'tags' => ['Работа', 'Покупки'],
    ];
    $makeCurl = fn ($method, $url, $payload) => 'curl --request '.$method.' "'.$url.'"'." \\\n"
        .'  --header "Authorization: Bearer API_KEY"'." \\\n"
        .'  --header "Content-Type: application/json"'." \\\n"
        .'  --header "Accept: application/json"'." \\\n"
        ."  --data '".json_encode($payload, JSON_UNESCAPED_UNICODE)."'";
    $makeJavascript = fn ($method, $url, $payload) => 'const response = await fetch("'.$url.'", {' . "\n"
        .'  method: "'.$method.'",'."\n"
        ."  headers: {\n"
        ."    \"Authorization\": \"Bearer API_KEY\",\n"
        ."    \"Content-Type\": \"application/json\",\n"
        ."    \"Accept\": \"application/json\"\n"
        ."  },\n"
        .'  body: JSON.stringify('.json_encode($payload, JSON_UNESCAPED_UNICODE).")\n"
        ."});\n\n"
        ."const result = await response.json();\n\n"
        ."if (response.ok) {\n"
        ."  console.log(result.data);\n"
        ."} else {\n"
        ."  console.error(result.errors ?? result.message);\n"
        .'}';
    $curl = $makeCurl('POST', $endpoint, $minimalRequest);
    $javascript = $makeJavascript('POST', $endpoint, $minimalRequest);
    $updateCurl = $makeCurl('PATCH', $updateEndpoint, $updateRequest);
    $updateJavascript = $makeJavascript('PATCH', $updateEndpoint, $updateRequest);
    $parameters = [
        ['title', 'string', '—', 'Название цели, до 255 символов. Обязательно при создании. При изменении можно опустить, но нельзя передать пустым.'],
        ['description', 'string | null', 'null', 'Описание цели в выбранном формате.'],
        ['description_format', 'string', '"raw"', 'Формат описания: raw, markdown или html.'],
        ['url', 'string | null', 'null', 'Ссылка, связанная с целью.'],
        ['tags', 'string[]', '[]', 'Теги цели. При изменении заменяют весь список; [] очищает его. Лишние пробелы и повторы без учёта регистра удаляются.'],
        ['target_amount', 'number | null', '0', 'Стоимость цели. До двух знаков после запятой.'],
        ['saved_amount', 'number | null', '0', 'Достигнутая или потраченная сумма. До двух знаков после запятой.'],
        ['priority', 'integer', '2', '1 — высокий, 2 — средний, 3 — низкий, 4 — архив.'],
        ['is_completed', 'boolean', 'false', 'Признак завершённой цели.'],
    ];
    $success = ['data' => [
        'id' => 42,
        'board_id' => 7,
        ...$fullRequest,
        'is_public' => false,
        'position' => 1,
        'created_at' => '2026-10-23T10:00:00.000000Z',
        'updated_at' => '2026-10-23T10:00:00.000000Z',
    ]];
    $unauthorized = ['message' => 'Неверный или отсутствующий API-ключ.'];
    $notFound = ['message' => 'Цель не найдена.'];
    $validation = [
        'message' => 'The title field is required.',
        'errors' => ['title' => ['The title field is required.']],
    ];
@endphp
<!DOCTYPE html>
<html lang="ru" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Документация API целей: авторизация ключом доски, создание и изменение целей, параметры и примеры запросов.">
    <title>Документация API | Цели</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="{{ asset('js/api-docs.js') }}" defer></script>
    <style>
        [hidden] { display: none !important; }
        [data-example-tab][aria-selected="true"] { background: #334155; color: #f1f5f9; }
        ::selection { background: #4338ca; color: #fff; }
        @media (prefers-reduced-motion: reduce) { html { scroll-behavior: auto; } }
    </style>
</head>
<body class="bg-slate-900 text-slate-300 antialiased">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-indigo-500 focus:px-4 focus:py-2 focus:text-white">К содержанию</a>
    <header class="sticky top-0 z-30 border-b border-slate-800 bg-slate-900/95 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-3 rounded-lg font-semibold text-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400/60">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-500/15 text-indigo-300">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <circle cx="12" cy="12" r="8.5" /><circle cx="12" cy="12" r="4" /><circle cx="12" cy="12" r=".75" fill="currentColor" />
                    </svg>
                </span>
                Цели <span class="font-normal text-slate-600">/</span> <span class="text-sm font-medium text-slate-400">API</span>
            </a>
            <a href="{{ url('/') }}" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs text-slate-400 transition hover:bg-slate-800 hover:text-slate-100">
                <span aria-hidden="true">←</span> К доске
            </a>
        </div>
    </header>

    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-12 lg:flex lg:items-start lg:gap-12">
        <aside class="sticky top-28 hidden w-48 shrink-0 lg:block">
            <p class="mb-4 text-[11px] font-semibold uppercase tracking-widest text-slate-500">Документация</p>
            <nav aria-label="Разделы документации" class="space-y-1">
                @foreach($sections as $anchor => $label)
                    <a href="#{{ $anchor }}" class="block rounded-lg px-3 py-2 text-sm text-slate-400 transition hover:bg-slate-800 hover:text-slate-100">{{ $label }}</a>
                @endforeach
            </nav>
            <div class="mt-7 space-y-3 border-t border-slate-800 pt-5">
                <div>
                    <span class="rounded bg-emerald-500/10 px-1.5 py-1 font-mono text-[10px] font-semibold text-emerald-300">POST</span>
                    <a href="#create-goal" class="ml-1.5 font-mono text-xs text-slate-400 hover:text-slate-100">/api/goals</a>
                </div>
                <div>
                    <span class="rounded bg-sky-500/10 px-1.5 py-1 font-mono text-[10px] font-semibold text-sky-300">PATCH</span>
                    <a href="#update-goal" class="ml-1 font-mono text-[11px] text-slate-400 hover:text-slate-100">/api/goals/{id}</a>
                </div>
            </div>
        </aside>

        <main id="main-content" class="min-w-0 flex-1">
            <div class="mb-10">
                <span class="inline-flex items-center gap-2 text-xs font-medium text-indigo-300"><span class="h-1.5 w-1.5 rounded-full bg-indigo-400"></span> API · 2 метода</span>
                <h1 class="mt-4 text-3xl font-semibold tracking-tight text-slate-100 sm:text-4xl">Документация API</h1>
                <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-400 sm:text-base">Создавайте и изменяйте цели в своей доске из скриптов и интеграций. Для первого запроса достаточно ключа доски и названия цели.</p>
                <div class="mt-6 flex flex-wrap items-center gap-3 rounded-2xl border border-slate-700/60 bg-slate-800/60 px-4 py-3">
                    <span class="rounded-md bg-emerald-500/10 px-2 py-1 font-mono text-xs font-semibold text-emerald-300">POST</span>
                    <code class="text-sm text-slate-200">/api/goals</code>
                    <span class="text-xs text-slate-500 sm:ml-auto">JSON · Bearer token</span>
                </div>
                <div class="mt-2 flex flex-wrap items-center gap-3 rounded-2xl border border-slate-700/60 bg-slate-800/60 px-4 py-3">
                    <span class="rounded-md bg-sky-500/10 px-2 py-1 font-mono text-xs font-semibold text-sky-300">PATCH</span>
                    <code class="text-sm text-slate-200">/api/goals/{id}</code>
                    <span class="text-xs text-slate-500 sm:ml-auto">JSON · Bearer token</span>
                </div>
            </div>

            <nav aria-label="Быстрая навигация" class="mb-8 flex gap-2 overflow-x-auto pb-2 lg:hidden">
                @foreach($sections as $anchor => $label)
                    <a href="#{{ $anchor }}" class="shrink-0 rounded-lg bg-slate-800 px-3 py-2 text-xs text-slate-400 hover:text-slate-100">{{ $label }}</a>
                @endforeach
            </nav>

            <div class="space-y-12 sm:space-y-16">
                <section id="quickstart" class="scroll-mt-24">
                    <h2 class="text-xl font-semibold text-slate-100">Быстрый старт</h2>
                    <ol class="mt-4 list-decimal space-y-2 pl-5 text-sm leading-6 text-slate-400 marker:text-slate-600">
                        <li>Откройте настройки нужной доски через шестерёнку рядом с заголовком.</li>
                        <li>Скопируйте API-ключ и замените им <code class="text-indigo-200">API_KEY</code> в примере.</li>
                        <li>Отправьте запрос. Цель появится первой в среднем приоритете.</li>
                    </ol>
                    <div data-code-tabs class="mt-5">
                        <div role="tablist" aria-label="Язык примера" class="mb-3 inline-flex gap-1 rounded-xl bg-slate-800/70 p-1">
                            <button id="example-curl-tab" data-example-tab="curl" role="tab" aria-controls="example-curl-panel" aria-selected="true" tabindex="0" type="button" class="rounded-lg px-3 py-1.5 text-xs font-medium text-slate-400 transition">cURL</button>
                            <button id="example-javascript-tab" data-example-tab="javascript" role="tab" aria-controls="example-javascript-panel" aria-selected="false" tabindex="-1" type="button" class="rounded-lg px-3 py-1.5 text-xs font-medium text-slate-400 transition">JavaScript</button>
                        </div>
                        <div id="example-curl-panel" role="tabpanel" aria-labelledby="example-curl-tab">
                            @include('partials.api-docs-code', ['label' => 'Минимальный запрос · cURL', 'code' => $curl])
                        </div>
                        <div id="example-javascript-panel" role="tabpanel" aria-labelledby="example-javascript-tab" hidden>
                            @include('partials.api-docs-code', ['label' => 'Минимальный запрос · JavaScript', 'code' => $javascript])
                        </div>
                    </div>
                </section>

                <section id="authentication" class="scroll-mt-24">
                    <h2 class="text-xl font-semibold text-slate-100">Авторизация</h2>
                    <p class="mt-4 text-sm leading-7 text-slate-400">Каждый запрос авторизуется ключом конкретной доски. Передавайте его в заголовке <code class="text-slate-200">Authorization</code>:</p>
                    <div class="mt-4">
                        @include('partials.api-docs-code', ['label' => 'Заголовки запроса', 'code' => "Authorization: Bearer API_KEY\nContent-Type: application/json\nAccept: application/json"])
                    </div>
                    <p class="mt-4 text-sm leading-7 text-slate-400">PIN-код, cookies и CSRF-токен для API не нужны. Ключи в URL и теле запроса не принимаются. При сбросе ключа в настройках прежний ключ сразу перестаёт работать.</p>
                </section>

                <section id="create-goal" class="scroll-mt-24">
                    <div class="flex flex-wrap items-center gap-3">
                        <h2 class="text-xl font-semibold text-slate-100">Создание цели</h2>
                        <span class="rounded-md bg-emerald-500/10 px-2 py-1 font-mono text-[11px] font-semibold text-emerald-300">POST</span>
                    </div>
                    <p class="mt-4 break-all rounded-xl bg-slate-800/60 px-4 py-3 font-mono text-xs leading-6 text-slate-300">{{ $endpoint }}</p>
                    <p class="mt-4 text-sm leading-7 text-slate-400">Обязательно только <code class="text-slate-200">title</code>. Остальные поля можно опустить. Доска определяется ключом, а новая цель становится первой в выбранном приоритете. Каждое успешное обращение создаёт новую цель.</p>
                    <h3 class="mb-3 mt-6 text-sm font-medium text-slate-200">Пример со всеми параметрами</h3>
                    @include('partials.api-docs-code', ['id' => 'request-body-example', 'label' => 'Тело запроса · JSON', 'code' => json_encode($fullRequest, $flags)])
                </section>

                <section id="update-goal" class="scroll-mt-24">
                    <div class="flex flex-wrap items-center gap-3">
                        <h2 class="text-xl font-semibold text-slate-100">Изменение цели</h2>
                        <span class="rounded-md bg-sky-500/10 px-2 py-1 font-mono text-[11px] font-semibold text-sky-300">PATCH</span>
                    </div>
                    <p class="mt-4 break-all rounded-xl bg-slate-800/60 px-4 py-3 font-mono text-xs leading-6 text-slate-300">{{ $updateEndpoint }}</p>
                    <p class="mt-4 text-sm leading-7 text-slate-400">Замените <code class="text-slate-200">42</code> на <code class="text-slate-200">id</code> цели из ответа на создание. Ключ должен принадлежать доске этой цели. Передавайте только поля, которые хотите изменить: остальные значения сохранятся.</p>
                    <p class="mt-3 text-sm leading-7 text-slate-400">Доступны те же <a href="#parameters" class="text-indigo-300 hover:text-indigo-200">параметры</a>, что и при создании, но все они необязательны. Пустой объект <code>{}</code> ничего не меняет. При смене <code>priority</code> цель перемещается в конец выбранной колонки; при прежнем приоритете её место сохраняется.</p>
                    <div class="mt-5 rounded-2xl bg-slate-800/50 p-4 text-xs leading-6 text-slate-400">
                        <p><strong class="font-medium text-slate-200">Очистка полей.</strong> Для описания и ссылки передайте <code>null</code>, для тегов — <code>[]</code>. Суммы можно обнулить значением <code>0</code> или <code>null</code>. Чтобы завершить цель, передайте <code>"is_completed": true</code>, чтобы вернуть в работу — <code>false</code>. Накопленная сумма при этом сама не меняется.</p>
                    </div>
                    <h3 class="mb-3 mt-6 text-sm font-medium text-slate-200">Пример частичного изменения</h3>
                    @include('partials.api-docs-code', ['id' => 'update-request-body-example', 'label' => 'Изменение цели · JSON', 'code' => json_encode($updateRequest, $flags)])
                    <div data-code-tabs class="mt-5">
                        <div role="tablist" aria-label="Язык примера изменения" class="mb-3 inline-flex gap-1 rounded-xl bg-slate-800/70 p-1">
                            <button id="update-curl-tab" data-example-tab="curl" role="tab" aria-controls="update-curl-panel" aria-selected="true" tabindex="0" type="button" class="rounded-lg px-3 py-1.5 text-xs font-medium text-slate-400 transition">cURL</button>
                            <button id="update-javascript-tab" data-example-tab="javascript" role="tab" aria-controls="update-javascript-panel" aria-selected="false" tabindex="-1" type="button" class="rounded-lg px-3 py-1.5 text-xs font-medium text-slate-400 transition">JavaScript</button>
                        </div>
                        <div id="update-curl-panel" role="tabpanel" aria-labelledby="update-curl-tab">
                            @include('partials.api-docs-code', ['label' => 'Изменение цели · cURL', 'code' => $updateCurl])
                        </div>
                        <div id="update-javascript-panel" role="tabpanel" aria-labelledby="update-javascript-tab" hidden>
                            @include('partials.api-docs-code', ['label' => 'Изменение цели · JavaScript', 'code' => $updateJavascript])
                        </div>
                    </div>
                </section>

                <section id="parameters" class="scroll-mt-24">
                    <h2 class="text-xl font-semibold text-slate-100">Параметры запроса</h2>
                    <p class="mb-5 mt-4 text-sm leading-7 text-slate-400">Передавайте параметры в JSON. Для <code>POST</code> действуют значения по умолчанию из таблицы. Для <code>PATCH</code> все поля необязательны, а непереданные значения остаются прежними.</p>
                    <div class="overflow-hidden rounded-2xl border border-slate-700/60">
                        <table class="w-full text-left text-xs leading-6 sm:text-sm">
                            <caption class="sr-only">Поля запросов создания и изменения цели</caption>
                            <thead class="bg-slate-800/70 text-xs text-slate-400">
                                <tr><th scope="col" class="px-3 py-3 font-medium sm:px-4">Поле</th><th scope="col" class="hidden px-4 py-3 font-medium md:table-cell">Тип</th><th scope="col" class="px-3 py-3 font-medium sm:px-4">Описание</th></tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800">
                                @foreach($parameters as [$name, $type, $default, $description])
                                    <tr class="align-top">
                                        <th scope="row" class="px-3 py-4 text-left font-normal sm:px-4">
                                            <code class="text-[11px] text-indigo-200 sm:text-xs">{{ $name }}</code>
                                            <span class="mt-1 block text-[10px] text-slate-500 md:hidden">{{ $type }}</span>
                                            @if($name === 'title')<span class="mt-1 block text-[10px] text-amber-300">Для POST</span>@endif
                                        </th>
                                        <td class="hidden whitespace-nowrap px-4 py-4 font-mono text-xs text-slate-500 md:table-cell">{{ $type }}</td>
                                        <td class="px-3 py-4 text-slate-400 sm:px-4">
                                            {{ $description }}
                                            @if($default !== '—')<span class="mt-1 block text-xs text-slate-500">По умолчанию: <code class="text-slate-300">{{ $default }}</code></span>@endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-5 space-y-3 rounded-2xl bg-slate-800/50 p-4 text-xs leading-6 text-slate-400">
                        <p><strong class="font-medium text-slate-200">Теги.</strong> Общая длина после нормализации, включая разделители, — до 255 символов. Запятые, точки с запятой и переносы строк внутри тега не поддерживаются.</p>
                        <p><strong class="font-medium text-slate-200">Суммы.</strong> От 0 до 9 999 999 999,99, максимум два знака после запятой. В JSON используйте точку как десятичный разделитель. Значение <code>null</code> сохраняется как 0.</p>
                        <p><strong class="font-medium text-slate-200">Доступ и порядок.</strong> Поля <code>board_id</code>, <code>position</code>, <code>is_public</code> и <code>public_token</code> передавать нельзя. Цель создаётся частной; опубликовать её можно в интерфейсе.</p>
                    </div>
                </section>

                <section id="response" class="scroll-mt-24">
                    <div class="flex flex-wrap items-center gap-3">
                        <h2 class="text-xl font-semibold text-slate-100">Успешный ответ</h2>
                        <span class="rounded-md bg-emerald-500/10 px-2 py-1 font-mono text-xs text-emerald-300">201 Created</span>
                        <span class="rounded-md bg-sky-500/10 px-2 py-1 font-mono text-xs text-sky-300">200 OK</span>
                    </div>
                    <p class="mb-5 mt-4 text-sm leading-7 text-slate-400">При создании возвращается <code class="text-slate-200">201 Created</code>, при изменении — <code class="text-slate-200">200 OK</code>. В обоих случаях актуальная цель находится в <code class="text-slate-200">data</code>. Поле <code>id</code> — её идентификатор, <code>board_id</code> — доска ключа, <code>position</code> — место в приоритете. Даты передаются в ISO 8601, в UTC. Ниже пример ответа на создание; ответ на изменение имеет ту же структуру.</p>
                    @include('partials.api-docs-code', ['label' => 'Пример ответа · JSON', 'code' => json_encode($success, $flags)])
                </section>

                <section id="errors" class="scroll-mt-24">
                    <h2 class="text-xl font-semibold text-slate-100">Ошибки</h2>
                    <p class="mt-4 text-sm leading-7 text-slate-400">Ошибки API возвращаются в JSON. При ошибках авторизации, поиска цели или проверки параметров изменения не сохраняются.</p>
                    <div class="mb-3 mt-6 flex flex-wrap items-center gap-3">
                        <span class="rounded-md bg-rose-500/10 px-2 py-1 font-mono text-xs text-rose-300">401 Unauthorized</span>
                        <h3 class="text-sm font-medium text-slate-200">Ключ не принят</h3>
                    </div>
                    <p class="mb-4 text-sm leading-7 text-slate-400">Проверьте наличие Bearer-заголовка и актуальность ключа. После сброса скопируйте новый ключ из настроек доски.</p>
                    @include('partials.api-docs-code', ['label' => 'Ошибка авторизации · JSON', 'code' => json_encode($unauthorized, $flags)])
                    <div class="mb-3 mt-7 flex flex-wrap items-center gap-3">
                        <span class="rounded-md bg-slate-700/60 px-2 py-1 font-mono text-xs text-slate-300">404 Not Found</span>
                        <h3 class="text-sm font-medium text-slate-200">Цель не найдена</h3>
                    </div>
                    <p class="mb-4 text-sm leading-7 text-slate-400">Для <code>PATCH</code> проверьте идентификатор цели и ключ её доски. Несуществующая цель и цель другой доски возвращают одинаковый ответ.</p>
                    @include('partials.api-docs-code', ['label' => 'Цель не найдена · JSON', 'code' => json_encode($notFound, $flags)])
                    <div class="mb-3 mt-7 flex flex-wrap items-center gap-3">
                        <span class="rounded-md bg-amber-500/10 px-2 py-1 font-mono text-xs text-amber-300">422 Unprocessable Content</span>
                        <h3 class="text-sm font-medium text-slate-200">Неверные параметры</h3>
                    </div>
                    <p class="mb-4 text-sm leading-7 text-slate-400">В <code class="text-slate-200">errors</code> перечислены поля и сообщения об ошибках. Например, если не передать название при создании или передать пустое название при изменении:</p>
                    @include('partials.api-docs-code', ['label' => 'Ошибка валидации · JSON', 'code' => json_encode($validation, $flags)])
                </section>
            </div>
            <footer class="mt-12 flex flex-wrap items-center justify-between gap-4 border-t border-slate-800 py-6 text-xs text-slate-500">
                <span>Цели · Документация API</span>
                <a href="#main-content" class="rounded px-1 py-1 transition hover:text-slate-200">Наверх ↑</a>
            </footer>
        </main>
    </div>
</body>
</html>
