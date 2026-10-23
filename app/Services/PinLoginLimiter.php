<?php

namespace App\Services;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PinLoginLimiter
{
    private const MAX_FAILURES = 5;

    private const COOLDOWN_SECONDS = 120;

    public function retryAfter(Request $request, string $scope = 'board'): int
    {
        $until = (int) Cache::get($this->key($request, $scope).':blocked-until', 0);

        return max(0, $until - now()->timestamp);
    }

    public function recordFailure(Request $request, string $scope = 'board'): int
    {
        $key = $this->key($request, $scope);

        try {
            return Cache::lock($key.':lock', 5)->block(3, function () use ($request, $scope, $key): int {
                if (($remaining = $this->retryAfter($request, $scope)) > 0) {
                    return $remaining;
                }

                $failures = (int) Cache::get($key.':failures', 0) + 1;

                if ($failures >= self::MAX_FAILURES) {
                    Cache::put($key.':blocked-until', now()->timestamp + self::COOLDOWN_SECONDS, self::COOLDOWN_SECONDS);
                    Cache::forget($key.':failures');

                    return self::COOLDOWN_SECONDS;
                }

                Cache::forever($key.':failures', $failures);

                return 0;
            });
        } catch (LockTimeoutException) {
            abort(429, 'Слишком много попыток. Повторите запрос позже.');
        }
    }

    public function clearFailures(Request $request, string $scope = 'board'): void
    {
        Cache::forget($this->key($request, $scope).':failures');
    }

    private function key(Request $request, string $scope): string
    {
        return 'pin-login:'.$scope.':'.hash('sha256', $request->ip() ?? 'unknown');
    }
}
