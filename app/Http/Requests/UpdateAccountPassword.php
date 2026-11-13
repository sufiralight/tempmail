<?php

namespace App\Http\Requests;

use App\Rules\AccountPassword;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateAccountPassword extends FormRequest
{
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'max:1024'],
            'password' => AccountPassword::rules(),
            'password_confirmation' => ['required', 'string'],
        ];
    }
}
