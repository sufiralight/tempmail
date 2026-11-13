<?php

namespace App\Auth;

use App\Models\User;
use Closure;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Throwable;

final class AccountMail
{
    public function sendResetLink(string $email): void
    {
        $this->deliver(static fn () => Password::sendResetLink(['email' => $email]));
    }

    public function sendVerification(User $user): bool
    {
        return $this->deliver(static fn () => $user->notify(new VerifyEmail));
    }

    private function deliver(Closure $send): bool
    {
        $mailer = config('mail.default');
        $transport = config('mail.mailers.'.$mailer.'.transport');
        // Log and composite transports may record messages containing account tokens.
        if (! in_array($transport, ['smtp', 'sendmail', 'ses', 'ses-v2', 'postmark', 'resend', 'mailgun'], true)) {
            return false;
        }

        try {
            $send();

            return true;
        } catch (Throwable) {
            Log::warning('Account notification could not be delivered.');

            return false;
        }
    }
}
