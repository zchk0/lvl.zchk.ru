<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Доски</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body class="min-h-screen bg-slate-900 text-slate-100 py-10">
    <main class="max-w-4xl mx-auto px-4">
        <div class="flex items-center justify-between gap-4 mb-7">
            <div>
                <h1 class="text-2xl sm:text-3xl font-semibold">Доски</h1>
                <p class="mt-1 text-sm text-slate-400">Создание и удаление досок с отдельными PIN-кодами.</p>
            </div>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button class="rounded-2xl border border-slate-600 px-4 py-2 text-sm text-slate-200 hover:bg-slate-800 transition">
                    Выйти
                </button>
            </form>
        </div>

        @if(session('status'))
            <div class="mb-4 rounded-2xl border border-emerald-400/40 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
                {{ session('status') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 rounded-2xl border border-red-400/40 bg-red-500/10 px-4 py-3 text-sm text-red-200">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <section class="rounded-3xl bg-slate-800 border border-slate-700 p-5 mb-6">
            <h2 class="text-lg font-semibold">Новая доска</h2>
            <form method="POST" action="{{ route('admin.boards.store') }}" class="mt-4 grid gap-3 sm:grid-cols-[1fr_140px_auto]">
                @csrf
                <input
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    placeholder="Название доски"
                    class="rounded-2xl border border-slate-600 bg-slate-900/60 px-4 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <input
                    type="text"
                    name="pin_code"
                    value="{{ old('pin_code') }}"
                    inputmode="numeric"
                    maxlength="4"
                    pattern="[0-9]{4}"
                    placeholder="PIN"
                    class="rounded-2xl border border-slate-600 bg-slate-900/60 px-4 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <button
                    type="submit"
                    class="rounded-2xl bg-indigo-500 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-600 transition">
                    Создать
                </button>
            </form>
        </section>

        <section class="rounded-3xl bg-slate-800 border border-slate-700 overflow-hidden">
            <div class="grid grid-cols-[1fr_90px_80px] gap-3 border-b border-slate-700 px-4 py-3 text-xs font-semibold uppercase text-slate-400">
                <div>Название</div>
                <div>Цели</div>
                <div></div>
            </div>
            @forelse($boards as $board)
                <div class="grid grid-cols-[1fr_90px_80px] items-center gap-3 border-b border-slate-700 px-4 py-3 last:border-b-0">
                    <div>
                        <div class="font-medium text-slate-100">{{ $board->name }}</div>
                        <div class="mt-1 text-xs text-slate-500">PIN: {{ $board->pin_code }}</div>
                    </div>
                    <div class="text-sm text-slate-300">{{ $board->goals_count }}</div>
                    <form method="POST" action="{{ route('admin.boards.destroy', $board) }}" onsubmit="return confirm('Удалить доску и все её цели?');">
                        @csrf
                        @method('DELETE')
                        <button class="rounded-xl border border-red-400/50 px-3 py-1.5 text-xs text-red-200 hover:bg-red-500/10 transition">
                            Удалить
                        </button>
                    </form>
                </div>
            @empty
                <div class="px-4 py-6 text-sm text-slate-500">Досок пока нет.</div>
            @endforelse
        </section>
    </main>
</body>
</html>
