<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PinCodeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Пути, которые не должны требовать PIN (форма ввода и обработчик)
        $except = [
            'pin',
            'pin/check',
            'share/*',
        ];

        // Не проверяем PIN для этих путей, иначе будет бесконечный редирект
        if ($request->is($except) || $request->is('pin/*')) {
            return $next($request);
        }

        $cookieName = env('PIN_COOKIE_NAME', 'pin_ok');

        // Если cookie уже есть и правильная — пропускаем
        if ($request->cookie($cookieName) === '1') {
            return $next($request);
        }

        // Иначе отправляем на страницу ввода PIN
        return redirect()->route('pin.form');
    }
}
