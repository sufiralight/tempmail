<?php

namespace App\Http\Controllers;

use App\Administration\AuditLog;
use App\Auth\AccountMail;
use App\Auth\AccountSessions;
use App\Http\Requests\UpdateAccountPassword;
use App\Http\Requests\UpdatePreferences;
use App\Http\Requests\UpdateProfile;
use App\Models\User;
use App\Rules\AccountPassword;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class ProfileController extends Controller
{
    public function __construct(private AuditLog $audit, private AccountSessions $sessions) {}

    public function show(): View
    {
        return view('profile.personal');
    }

    public function update(UpdateProfile $request, AccountMail $mail): RedirectResponse
    {
        $data = $request->validated();
        try {
            [$user, $changed, $previousEmail] = DB::transaction(function () use ($request, $data): array {
                $user = $this->lockedUser($request);
                $previousEmail = $user->email;
                $changed = $user->email !== $data['email'];
                if ($changed) {
                    $this->confirmPassword($user, $data['current_password'] ?? null);
                    DB::table(config('auth.passwords.users.table'))->whereIn('email', [$user->email, $data['email']])->delete();
                    $user->forceFill(['email_verified_at' => null, 'remember_token' => Str::random(60)]);
                    $this->sessions->revokeOthers($request, $user);
                }
                $user->fill(['name' => $data['name'], 'email' => $data['email']]);
                if ($user->isDirty()) {
                    $user->save();
                    $this->audit->record($user, $changed ? 'account.email_changed' : 'account.profile_updated', 'user', $user->id);
                }

                return [$user, $changed, $previousEmail];
            }, 3);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['email' => 'This email is already registered.'])->redirectTo('/profile');
        }

        if ($changed) {
            $mail->sendSecurityNotice($previousEmail, 'Your account email address changed.');
            $sent = $mail->sendVerification($user);

            return redirect('/profile')->with('status', $sent
                ? 'Profile saved. Check your new address for the verification email.'
                : 'Profile saved. Verification email could not be sent. Request another link from Security.');
        }

        return redirect('/profile')->with('status', 'Profile saved.');
    }

    public function security(Request $request): View
    {
        $user = $request->user();

        return view('profile.security', [
            'sessionCount' => $this->sessions->active($user)->count(),
            'events' => DB::table('administrative_audits')->where('target_type', 'user')
                ->where('target_id', $user->id)->where('actor_id', $user->id)->whereLike('action', 'account.%')
                ->orderByDesc('id')->limit(8)->get(),
        ]);
    }

    public function password(UpdateAccountPassword $request, AccountMail $mail): RedirectResponse
    {
        $user = DB::transaction(function () use ($request): User {
            $user = $this->lockedUser($request);
            $this->confirmPassword($user, $request->validated('current_password'));
            $user->forceFill(['password' => $request->validated('password'), 'remember_token' => Str::random(60)])->save();
            DB::table(config('auth.passwords.users.table'))->where('email', $user->email)->delete();
            $this->sessions->revokeOthers($request, $user);
            $this->audit->record($user, 'account.password_changed', 'user', $user->id);

            return $user;
        }, 3);

        Auth::setUser($user);
        $this->sessions->retire($request);
        $request->session()->regenerate(true);
        $mail->sendSecurityNotice($user->email, 'Your account password changed.');

        return redirect('/profile/security')->with('status', 'Password changed. Other sessions were signed out.');
    }

    public function sessions(Request $request): View
    {
        return view('profile.sessions', [
            'sessions' => $this->sessions->active($request->user())
                ->select('id', 'device', 'last_seen')->selectRaw('CASE WHEN digest = ? THEN 1 ELSE 0 END AS current_device', [$this->sessions->digest($request)])
                ->orderByDesc('current_device')->orderByDesc('last_seen')->paginate(20),
        ]);
    }

    public function revoke(Request $request, AccountMail $mail, ?string $session = null): RedirectResponse
    {
        $data = $request->validate(['current_password' => ['required', 'string', 'max:1024']]);
        [$count, $email] = DB::transaction(function () use ($request, $data, $session): array {
            $user = $this->lockedUser($request);
            $this->confirmPassword($user, $data['current_password']);
            $count = $this->sessions->revokeOthers($request, $user, $session);
            if ($count > 0) {
                $this->audit->record($user, 'account.sessions_revoked', 'user', $user->id, ['count' => (string) $count]);
            }

            return [$count, $user->email];
        }, 3);
        if ($count > 0) {
            $mail->sendSecurityNotice($email, 'Other sessions were signed out of your account.');
        }

        return redirect('/profile/sessions')->with('status', $count > 0 ? 'Other sessions signed out.' : 'No matching other active sessions.');
    }

    public function preferences(): View
    {
        return view('profile.preferences');
    }

    public function savePreferences(UpdatePreferences $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $user = $this->lockedUser($request);
            $user->forceFill($request->validated());
            if ($user->isDirty()) {
                $user->save();
                $this->audit->record($user, 'account.preferences_updated', 'user', $user->id);
            }
        }, 3);

        return redirect('/profile/preferences')->with('status', 'Preferences saved.');
    }

    private function lockedUser(Request $request): User
    {
        return User::query()->whereKey($request->user()->getAuthIdentifier())->lockForUpdate()->firstOrFail();
    }

    private function confirmPassword(User $user, #[\SensitiveParameter] ?string $password): void
    {
        if ($password === null || (AccountPassword::usesBcrypt() && strlen($password) > AccountPassword::BCRYPT_BYTES)
            || str_contains($password, "\0") || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages(['current_password' => 'Enter your current password to confirm this change.']);
        }
    }
}
