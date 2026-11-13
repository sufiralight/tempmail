<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Rules\AccountPassword;

final class RegisterAccount extends LoginRequest
{
    protected $redirectRoute = 'register';

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [...parent::rules()['email'], 'unique:'.User::class.',email'],
            'password' => AccountPassword::rules(),
            'password_confirmation' => ['required', 'string'],
            'is_super_admin' => ['missing'],
            'role' => ['missing'],
            'is_active' => ['missing'],
        ];
    }
}
