<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResetAccountPassword;
use App\Http\Requests\SendResetLink;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class PasswordRecoveryController extends Controller
{
    public function requestLink(): View
    {
        return view('auth.forgot-password');
    }

    public function sendLink(SendResetLink $request): RedirectResponse
    {
        $mailer = config('mail.default');
        $transport = config('mail.mailers.'.$mailer.'.transport');
        // Exclude log and composite mailers that could fall back to logging a reset token.
        if (in_array($transport, ['smtp', 'sendmail', 'ses', 'ses-v2', 'postmark', 'resend', 'mailgun'], true)) {
            try {
                Password::sendResetLink($request->safe()->only(['email']));
            } catch (Throwable) {
                Log::warning('Password reset notification could not be sent.');
            }
        }

        return (new RedirectResponse('/forgot-password'))->setSession(app(Store::class))
            ->with('status', 'If this account can receive email, a password reset link will be sent.');
    }

    public function showReset(string $token): View
    {
        abort_unless(preg_match('/\A[a-f0-9]{64}\z/', $token), 404);

        return view('auth.reset-password', compact('token'));
    }

    public function reset(ResetAccountPassword $request): RedirectResponse
    {
        try {
            $status = Password::reset($request->safe()->only(['email', 'password', 'password_confirmation', 'token']),
                function (User $user, #[\SensitiveParameter] string $password): void {
                    $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
                    event(new PasswordReset($user));
                });
        } catch (Throwable) {
            Log::warning('Password reset operation failed.');
            $status = Password::INVALID_TOKEN;
        }

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => 'This password reset link is invalid or expired.'])
                ->redirectTo('/forgot-password');
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Your password was reset. Sign in with your new password.');
    }
}
