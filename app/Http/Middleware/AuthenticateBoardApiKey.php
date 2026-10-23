<?php

namespace App\Http\Middleware;

use App\Models\Board;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateBoardApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        $board = is_string($token) && preg_match('/\Alvl_[a-zA-Z0-9]{64}\z/', $token)
            ? Board::where('api_key_hash', hash('sha256', $token))->first()
            : null;

        if (! $board) {
            return response()->json(
                ['message' => 'Неверный или отсутствующий API-ключ.'],
                401,
                ['WWW-Authenticate' => 'Bearer'],
            );
        }

        $request->attributes->set('api_board', $board);

        return $next($request);
    }
}
