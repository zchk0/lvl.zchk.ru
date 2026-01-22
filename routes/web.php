<?php

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

    $correctPin = env('PIN_CODE', '1011');

    if ($request->input('pin') !== $correctPin) {
        return back()
            ->withErrors(['pin' => 'Неверный PIN-код'])
            ->withInput();
    }

    $cookieName = env('PIN_COOKIE_NAME', 'pin_ok');
    $minutes    = (int) env('PIN_COOKIE_MINUTES', 60);

    $cookie = cookie($cookieName, '1', $minutes, '/');

    return redirect()->intended('/')
        ->withCookie($cookie);
})->name('pin.check');