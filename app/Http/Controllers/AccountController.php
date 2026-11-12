<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterAccount;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

final class AccountController extends Controller
{
    public function login(): View
    {
        return view('auth.login');
    }

    public function authenticate(LoginRequest $request): RedirectResponse
    {
        try {
            $authenticated = Auth::attempt($request->safe()->only(['email', 'password']));
        } catch (QueryException) {
            Log::warning('Account authentication database operation failed.');
            $authenticated = false;
        }

        if (! $authenticated) {
            throw ValidationException::withMessages(['email' => 'The email or password is incorrect.'])->redirectTo('/login');
        }

        $request->session()->regenerate();
        $intended = $request->session()->pull('url.intended');

        return new RedirectResponse($intended === url('/admin') && $request->user()?->can('access-admin')
            ? '/admin'
            : '/dashboard');
    }

    public function register(): View
    {
        return view('auth.register');
    }

    public function create(RegisterAccount $request): RedirectResponse
    {
        try {
            $user = User::create($request->safe()->only(['name', 'email', 'password']));
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['email' => 'This email is already registered.'])->redirectTo('/register');
        } catch (QueryException) {
            Log::warning('Account registration database operation failed.');
            throw ValidationException::withMessages(['email' => 'Registration is temporarily unavailable. Try again later.'])->redirectTo('/register');
        }

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget('url.intended');

        return new RedirectResponse('/dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return new RedirectResponse('/login', 303);
    }
}
