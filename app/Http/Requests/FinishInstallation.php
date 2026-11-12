<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Session\Store;
use Illuminate\Support\Str;

final class FinishInstallation extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => Str::lower($this->input('email'))]);
        }
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'password' => ['bail', 'required', 'string', 'min:12', 'max:1024', 'confirmed',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (str_contains($value, "\0") || (config('hashing.driver', 'bcrypt') === 'bcrypt' && strlen($value) > 72)) {
                        $fail('Use a password that fits the supported length and character limits.');
                    }
                },
            ],
            'password_confirmation' => ['required', 'string'],
            'role' => ['missing'],
            'is_super_admin' => ['missing'],
        ];
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException((new RedirectResponse('/install/admin'))->setSession(app(Store::class))->withErrors($validator));
    }

    /** @return array{name: string, email: string, password: string} */
    public function administrator(): array
    {
        return $this->safe()->only(['name', 'email', 'password']);
    }
}
