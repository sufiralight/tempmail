<?php

namespace App\Http\Middleware;

use App\Auth\AccountSessions;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class TrackAccountSession
{
    public function __construct(private AccountSessions $sessions) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user instanceof User && ! $this->sessions->observe($request, $user)) {
            Auth::logoutCurrentDevice();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login');
        }

        $original = app()->getLocale();
        try {
            if ($user instanceof User && $user->interface_locale === 'en') {
                app()->setLocale('en');
            }

            return $next($request);
        } finally {
            if (app()->getLocale() !== $original) {
                app()->setLocale($original);
            }
        }
    }
}
