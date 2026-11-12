<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

final class ThrottleInstallation
{
    public function handle(Request $request, Closure $next): Response
    {
        $cache = Cache::store('file');
        $store = $cache instanceof Repository ? $cache->getStore() : null;
        abort_unless($store instanceof LockProvider, 503);

        $limiter = new RateLimiter($cache);
        $key = 'installer:'.hash('sha256', $request->route()->getName().'|'.$request->server('REMOTE_ADDR'));
        $retry = $store->lock($key.':lock', 5)->block(2, function () use ($limiter, $key): int {
            if ($limiter->tooManyAttempts($key, 5)) {
                return max(1, $limiter->availableIn($key));
            }

            $limiter->hit($key, 60);

            return 0;
        });

        if ($retry > 0) {
            return response()->view('installation.notice', [
                'title' => 'Too many attempts',
                'message' => 'Wait a minute before trying setup again.',
            ], 429, ['Retry-After' => (string) $retry]);
        }

        return $next($request);
    }
}
