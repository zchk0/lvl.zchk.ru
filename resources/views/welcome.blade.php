<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Цели</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="https://lvl.zchk.ru/favicon.svg" type="image/svg+xml">
    <script src="https://cdn.tailwindcss.com"></script>
    @livewireStyles
    <style>
        /* Скрыть скроллбар, но сохранить прокрутку */
        .overscroll-contain {
            scrollbar-width: none; -ms-overflow-style: none;
        }
        .overscroll-contain::-webkit-scrollbar {
            width: 0; height: 0;
        }
    </style>
</head>
<body class="bg-slate-900">
    @livewire('goals-board')

    @livewireScripts
    <script src="{{ asset('js/modal-scroll-lock.js') }}" defer></script>
    <script src="{{ asset('js/goal-dnd.js') }}" defer></script>
    <script src="{{ asset('js/subgoal-dnd.js') }}" defer></script>
    <script src="{{ asset('js/subgoal-autosize.js') }}" defer></script>
</body>
</html>
