<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        return self::apply($request, $next($request));
    }

    public static function apply(Request $request, Response $response): Response
    {
        $headers = $response->headers;
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Referrer-Policy', 'no-referrer');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $picker = $request->routeIs('media.picker') && $response->isSuccessful();
        $headers->set('X-Frame-Options', $picker ? 'SAMEORIGIN' : 'DENY');
        $policy = $headers->get('Content-Security-Policy');
        if ($policy === null || ! str_contains($policy, 'frame-ancestors')) {
            $headers->set('Content-Security-Policy', ($policy ? rtrim($policy, ';').'; ' : "object-src 'none'; base-uri 'self'; ").'frame-ancestors '.($picker ? "'self'" : "'none'"));
        }

        if (config('app.env') === 'production' && str_starts_with((string) config('app.url'), 'https://') && $request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        return $response;
    }
}
