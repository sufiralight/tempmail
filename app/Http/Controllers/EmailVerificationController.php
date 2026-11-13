<?php

namespace App\Http\Controllers;

use App\Auth\AccountMail;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class EmailVerificationController extends Controller
{
    public function notice(Request $request): View|RedirectResponse
    {
        return $request->user()?->hasVerifiedEmail()
            ? new RedirectResponse('/dashboard')
            : view('auth.verify-email');
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();
        $request->session()->flash('status', 'Your email address is verified.');

        return new RedirectResponse('/dashboard');
    }

    public function resend(Request $request, AccountMail $mail): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        if ($user->hasVerifiedEmail()) {
            return new RedirectResponse('/dashboard');
        }

        $request->session()->flash('status', $mail->sendVerification($user)
            ? 'A new verification link was sent.'
            : 'Verification email could not be sent. Try again later or contact support.');

        return new RedirectResponse('/email/verify');
    }
}
