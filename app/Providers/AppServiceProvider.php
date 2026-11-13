<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\DevCommands;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        DevCommands::except('queue');

        Gate::define('access-admin', static fn (User $user): bool => $user->isSuperAdmin());
        ResetPassword::createUrlUsing(static fn (User $user, string $token): string => rtrim((string) config('app.url'), '/').route('password.reset', [
            'token' => $token, 'email' => $user->getEmailForPasswordReset(),
        ], absolute: false)
        );
        VerifyEmail::createUrlUsing(static fn (User $user): string => rtrim((string) config('app.url'), '/').URL::temporarySignedRoute(
            'verification.verify', now()->addMinutes((int) config('auth.verification.expire', 60)),
            ['id' => $user->getKey(), 'hash' => sha1($user->getEmailForVerification())], absolute: false,
        ));
        RateLimiter::for('login', static function (Request $request): array {
            $peer = (string) $request->server('REMOTE_ADDR');
            $email = $request->input('email');
            $account = is_string($email) ? Str::lower(trim($email)) : '';

            return [
                Limit::perMinute(20)->by('peer:'.$peer),
                Limit::perMinute(5)->by('account:'.hash('sha256', $peer.'|'.$account)),
            ];
        });
        RateLimiter::for('register', static fn (Request $request) => Limit::perMinute(5)->by((string) $request->server('REMOTE_ADDR')));
        RateLimiter::for('password-recovery', static fn (Request $request) => Limit::perMinute(5)->by((string) $request->server('REMOTE_ADDR')));
        RateLimiter::for('verification-resend', static fn (Request $request) => Limit::perMinute(3)->by((string) $request->user()?->getAuthIdentifier()));
    }
}
