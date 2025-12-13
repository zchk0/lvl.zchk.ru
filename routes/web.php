<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

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