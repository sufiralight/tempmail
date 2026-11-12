<?php

namespace App\Http\Requests;

use App\Rules\AccountPassword;

final class ResetAccountPassword extends SendResetLink
{
    public function rules(): array
    {
        return parent::rules() + [
            'token' => ['required', 'string', 'regex:/\A[a-f0-9]{64}\z/'],
            'password' => AccountPassword::rules(),
            'password_confirmation' => ['required', 'string'],
        ];
    }

    protected function getRedirectUrl(): string
    {
        $token = $this->input('token');

        return is_string($token) && preg_match('/\A[a-f0-9]{64}\z/', $token)
            ? route('password.reset', ['token' => $token], absolute: false)
            : route('password.request', absolute: false);
    }
}
