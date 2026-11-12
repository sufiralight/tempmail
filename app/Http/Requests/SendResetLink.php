<?php

namespace App\Http\Requests;

class SendResetLink extends LoginRequest
{
    protected $redirectRoute = 'password.request';

    public function rules(): array
    {
        return ['email' => parent::rules()['email']];
    }
}
