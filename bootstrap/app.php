<?php

use App\Http\Middleware\PinCodeMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // навешиваем на web-группу проверку PIN
        $middleware->web(append: [
            PinCodeMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->respond(function (Response $response) {
            // перезагрузка в случае 419 Page Expired
            if ($response->getStatusCode() === 419) {
                return redirect()->back()
                    ->with('message', 'Сессия истекла. Страница обновлена, повторите действие.');
            }
            return $response;
        });
    })->create();
