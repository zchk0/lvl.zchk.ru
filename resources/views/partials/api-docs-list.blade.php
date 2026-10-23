@php
    $listEndpoint = route('api.goals.index');
    $listQuery = ['priority' => 2, 'is_completed' => 0, 'tags' => ['Работа'], 'per_page' => 20, 'page' => 1];
    $listUrl = $listEndpoint.'?'.http_build_query($listQuery, '', '&', PHP_QUERY_RFC3986);
    $curlLines = [
        'curl --get "'.$listEndpoint.'"',
        '  --header "Authorization: Bearer API_KEY"',
        '  --header "Accept: application/json"',
    ];
    foreach ($listQuery as $field => $value) {
        foreach (is_array($value) ? $value : [$value] as $item) {
            $name = $field.(is_array($value) ? '[]' : '');
            $curlLines[] = '  --data-urlencode "'.$name.'='.$item.'"';
        }
    }
    $listCurl = implode(" \\\n", $curlLines);
    $listJavascript = 'const response = await fetch("'.$listUrl.'", {' . "\n"
        ."  headers: {\n"
        ."    \"Authorization\": \"Bearer API_KEY\",\n"
        ."    \"Accept\": \"application/json\"\n"
        ."  }\n"
        ."});\n\n"
        ."const result = await response.json();\n\n"
        ."if (response.ok) {\n"
        ."  console.log(result.data, result.meta);\n"
        ."} else {\n"
        ."  console.error(result.errors ?? result.message);\n"
        .'}';
    $listParameters = [
        ['page', 'Номер страницы от 1. По умолчанию 1.'],
        ['per_page', 'Целей на странице: от 1 до 100. По умолчанию 20.'],
        ['q', 'Поиск подстроки в названии, описании и тегах, до 255 символов.'],
        ['id', 'Точный идентификатор цели.'],
        ['title', 'Подстрока в названии, до 255 символов.'],
        ['description', 'Подстрока в описании, до 2000 символов.'],
        ['url', 'Подстрока в ссылке, до 2048 символов.'],
        ['description_format', 'Точный формат описания: raw, markdown или html.'],
        ['priority', '1 — высокий, 2 — средний, 3 — низкий, 4 — архив.'],
        ['is_completed', 'Завершена: 1 или true. В работе: 0 или false.'],
        ['is_public', 'Публичная: 1 или true. Частная: 0 или false.'],
        ['position', 'Точная позиция внутри приоритета, целое число от 0.'],
        ['target_amount', 'Точная стоимость цели.'],
        ['saved_amount', 'Точная достигнутая или потраченная сумма.'],
        ['target_amount_min, target_amount_max', 'Минимальная и максимальная стоимость, включая границы.'],
        ['saved_amount_min, saved_amount_max', 'Минимальная и максимальная достигнутая сумма, включая границы.'],
        ['created_from, created_to', 'Период создания в формате YYYY-MM-DD. Дни включаются целиком, в UTC.'],
        ['updated_from, updated_to', 'Период изменения в формате YYYY-MM-DD. Дни включаются целиком, в UTC.'],
        ['tags[]', 'Список точных тегов, максимум 50. Один тег — до 255 символов, без запятых, точек с запятой и переносов строк.'],
        ['tags_mode', 'all — должны присутствовать все теги, any — хотя бы один. По умолчанию all.'],
        ['sort', 'Поле сортировки: id, title, priority, position, target_amount, saved_amount, is_completed, is_public, created_at или updated_at.'],
        ['direction', 'Направление для sort: asc или desc. По умолчанию asc.'],
    ];
    $listResponse = [
        'data' => [$goalExample],
        'links' => ['first' => $listUrl, 'last' => $listUrl, 'prev' => null, 'next' => null],
        'meta' => [
            'current_page' => 1,
            'from' => 1,
            'last_page' => 1,
            'links' => [
                ['url' => null, 'label' => __('pagination.previous'), 'page' => null, 'active' => false],
                ['url' => $listUrl, 'label' => '1', 'page' => 1, 'active' => true],
                ['url' => null, 'label' => __('pagination.next'), 'page' => null, 'active' => false],
            ],
            'path' => $listEndpoint,
            'per_page' => 20,
            'to' => 1,
            'total' => 1,
        ],
    ];
@endphp
<section id="list-goals" class="scroll-mt-24">
    <div class="flex flex-wrap items-center gap-3">
        <h2 class="text-xl font-semibold text-slate-100">Получение целей</h2>
        <span class="rounded-md bg-violet-500/10 px-2 py-1 font-mono text-[11px] font-semibold text-violet-300">GET</span>
    </div>
    <p class="mt-4 break-all rounded-xl bg-slate-800/60 px-4 py-3 font-mono text-xs leading-6 text-slate-300">{{ $listEndpoint }}</p>
    <p class="mt-4 text-sm leading-7 text-slate-400">Возвращает цели доски API-ключа. Без фильтров доступны все её цели, включая завершённые и архивные. Передавайте фильтры в строке запроса; тело запроса не используется. Поле <code>board_id</code> передавать нельзя.</p>
    <p class="mt-3 text-sm leading-7 text-slate-400">Разные фильтры объединяются условием «И». Поиск текста и сравнение тегов работают без учёта регистра, в том числе для кириллицы. Теги сравниваются целиком: <code>IVAN</code> не совпадает с <code>IVANOV</code>. Пробелы нормализуются, повторы тегов игнорируются. Символы <code>%</code> и <code>_</code> в текстовом поиске воспринимаются буквально.</p>
    <h3 class="mb-3 mt-6 text-sm font-medium text-slate-200">Пример: незавершённые цели среднего приоритета с тегом «Работа»</h3>
    <div data-code-tabs>
        <div role="tablist" aria-label="Язык примера получения" class="mb-3 inline-flex gap-1 rounded-xl bg-slate-800/70 p-1">
            <button id="list-curl-tab" data-example-tab="curl" role="tab" aria-controls="list-curl-panel" aria-selected="true" tabindex="0" type="button" class="rounded-lg px-3 py-1.5 text-xs font-medium text-slate-400 transition">cURL</button>
            <button id="list-javascript-tab" data-example-tab="javascript" role="tab" aria-controls="list-javascript-panel" aria-selected="false" tabindex="-1" type="button" class="rounded-lg px-3 py-1.5 text-xs font-medium text-slate-400 transition">JavaScript</button>
        </div>
        <div id="list-curl-panel" role="tabpanel" aria-labelledby="list-curl-tab">
            @include('partials.api-docs-code', ['label' => 'Получение целей · cURL', 'code' => $listCurl])
        </div>
        <div id="list-javascript-panel" role="tabpanel" aria-labelledby="list-javascript-tab" hidden>
            @include('partials.api-docs-code', ['label' => 'Получение целей · JavaScript', 'code' => $listJavascript])
        </div>
    </div>
    <p class="mt-4 text-sm leading-7 text-slate-400">Для нескольких тегов повторите параметр: <code class="break-all text-slate-200">tags[]=IVAN&amp;tags[]=Работа&amp;tags_mode=all</code>. Закодированный вариант <code>tags%5B0%5D</code> в примерах означает первый элемент массива <code>tags[]</code>.</p>

    <h3 id="list-filters" class="mb-4 mt-8 scroll-mt-24 text-lg font-semibold text-slate-100">Фильтры и пагинация</h3>
    <div class="overflow-hidden rounded-2xl border border-slate-700/60">
        <table class="w-full table-fixed text-left text-xs leading-6 sm:text-sm">
            <caption class="sr-only">Параметры GET /api/goals</caption>
            <thead class="bg-slate-800/70 text-xs text-slate-400">
                <tr><th scope="col" class="w-2/5 px-3 py-3 font-medium sm:w-1/3 sm:px-4">Параметр</th><th scope="col" class="px-3 py-3 font-medium sm:px-4">Описание</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @foreach($listParameters as [$name, $description])
                    <tr class="align-top">
                        <th scope="row" class="break-words px-3 py-4 text-left font-normal sm:px-4"><code class="text-[11px] text-indigo-200 sm:text-xs">{{ $name }}</code></th>
                        <td class="break-words px-3 py-4 text-slate-400 sm:px-4">{{ $description }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="mt-4 text-xs leading-6 text-slate-400">Фильтры необязательны; пустые значения передавать не нужно. Суммы — от 0 до 9 999 999 999,99, максимум два знака после точки. Конец диапазона не может быть меньше начала. Без <code>sort</code> порядок: приоритет, позиция и ID по возрастанию. При одинаковом значении выбранного поля сортировки порядок определяется ID по возрастанию.</p>

    <div class="mb-4 mt-8 flex flex-wrap items-center gap-3">
        <h3 class="text-lg font-semibold text-slate-100">Ответ со списком</h3>
        <span class="rounded-md bg-violet-500/10 px-2 py-1 font-mono text-xs text-violet-300">200 OK</span>
    </div>
    <p class="mb-4 text-sm leading-7 text-slate-400"><code class="text-slate-200">data</code> — массив целей, <code>meta.total</code> — общее количество после фильтрации, <code>meta.last_page</code> — число страниц. <code>links.next</code> и <code>links.prev</code> содержат ссылки с сохранёнными фильтрами или <code>null</code>. При переходе по ссылкам передавайте тот же Bearer-заголовок.</p>
    @include('partials.api-docs-code', ['label' => 'Пример списка · JSON', 'code' => json_encode($listResponse, $jsonFlags)])
    <p class="mt-4 text-sm leading-7 text-slate-400">Если совпадений нет или запрошена страница за пределами списка, вернётся <code>200 OK</code> с пустым <code>data</code>; <code>meta.total</code> при этом отражает количество всех совпадений. Неверный ключ возвращает <code>401</code>, неверные фильтры или размер страницы — <code>422</code> с ошибками в <code>errors</code>.</p>
</section>
