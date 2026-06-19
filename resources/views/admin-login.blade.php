<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Администратор</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body class="min-h-screen flex items-center justify-center bg-slate-900 text-slate-100">
    <div class="w-full max-w-sm mx-4 bg-slate-800 rounded-3xl shadow-2xl px-8 py-10">
        <h1 class="text-2xl font-semibold">Администратор</h1>
        <p class="mt-2 text-sm text-slate-400">Введите PIN администратора.</p>

        <form method="POST" action="{{ route('admin.login.check') }}" class="mt-6 space-y-4">
            @csrf
            <input
                type="password"
                name="pin"
                inputmode="numeric"
                autocomplete="current-password"
                class="w-full rounded-2xl border border-slate-600 bg-slate-900/60 px-4 py-3 text-center text-xl font-semibold outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                autofocus>

            @error('pin')
                <div class="text-sm text-red-400">{{ $message }}</div>
            @enderror

            <button
                type="submit"
                class="w-full rounded-2xl bg-indigo-500 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-600 transition">
                Войти
            </button>
        </form>
    </div>
</body>
</html>
