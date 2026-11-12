<?php

namespace App\Rules;

use Closure;

final class AccountPassword
{
    /** @return list<string|Closure> */
    public static function rules(bool $confirmed = true): array
    {
        $rules = ['bail', 'required', 'string', 'max:1024'];

        if ($confirmed) {
            array_push($rules, 'min:12', 'confirmed');
        }

        $rules[] = static function (string $attribute, string $value, Closure $fail): void {
            if (str_contains($value, "\0") || (config('hashing.driver', 'bcrypt') === 'bcrypt' && strlen($value) > 72)) {
                $fail('Use a password that fits the supported length and character limits.');
            }
        };

        return $rules;
    }
}
