<?php

use App\Models\Board;
use App\Models\Goal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/share/{token}', function (string $token) {
    $goal = Goal::query()
        ->where('public_token', $token)
        ->where('is_public', true)
        ->firstOrFail();
    return view('goal-public', [
        'goal' => $goal,
    ]);
})->where('token', '[A-Za-z0-9]+')->name('goals.share');

Route::get('/pin', function () {
    return view('pin');
})->name('pin.form');

Route::post('/pin/check', function (Request $request) {
    $request->validate([
        'pin' => ['required', 'string'],
    ]);

    $board = Board::query()
        ->where('pin_code', $request->input('pin'))
        ->first();

    if (! $board) {
        return back()
            ->withErrors(['pin' => 'Неверный PIN-код'])
            ->withInput();
    }

    $request->session()->put('board_id', $board->id);

    return redirect()->intended('/');
})->name('pin.check');

Route::post('/pin/logout', function (Request $request) {
    $request->session()->forget('board_id');

    return redirect()->route('pin.form');
})->name('pin.logout');

Route::get('/admin/login', function () {
    return view('admin-login');
})->name('admin.login');

Route::post('/admin/login', function (Request $request) {
    $request->validate([
        'pin' => ['required', 'string'],
    ]);

    $adminPin = env('ADMIN_PIN_CODE', env('PIN_CODE', '1011'));
    if ($request->input('pin') !== $adminPin) {
        return back()
            ->withErrors(['pin' => 'Неверный PIN администратора'])
            ->withInput();
    }

    $request->session()->put('admin_ok', true);

    return redirect()->route('admin.boards');
})->name('admin.login.check');

Route::post('/admin/logout', function (Request $request) {
    $request->session()->forget('admin_ok');

    return redirect()->route('admin.login');
})->name('admin.logout');

Route::get('/admin', function (Request $request) {
    if (! $request->session()->get('admin_ok')) {
        return redirect()->route('admin.login');
    }

    return view('admin-boards', [
        'boards' => Board::query()
            ->withCount('goals')
            ->orderBy('name')
            ->get(),
    ]);
})->name('admin.boards');

Route::post('/admin/boards', function (Request $request) {
    if (! $request->session()->get('admin_ok')) {
        return redirect()->route('admin.login');
    }

    $data = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'pin_code' => ['required', 'digits:4', 'unique:boards,pin_code'],
    ]);

    Board::create($data);

    return redirect()
        ->route('admin.boards')
        ->with('status', 'Доска создана');
})->name('admin.boards.store');

Route::delete('/admin/boards/{board}', function (Request $request, Board $board) {
    if (! $request->session()->get('admin_ok')) {
        return redirect()->route('admin.login');
    }

    if (Board::count() <= 1) {
        return redirect()
            ->route('admin.boards')
            ->withErrors(['board' => 'Нельзя удалить последнюю доску']);
    }

    if ((int) $request->session()->get('board_id', 0) === $board->id) {
        $request->session()->forget('board_id');
    }

    $board->delete();

    return redirect()
        ->route('admin.boards')
        ->with('status', 'Доска удалена');
})->name('admin.boards.destroy');
