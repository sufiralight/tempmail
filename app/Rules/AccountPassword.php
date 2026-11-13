<?php

namespace App\Rules;

use Closure;

final class AccountPassword
{
    public const MINIMUM = 12;

    public const MAXIMUM = 1024;

    public const BCRYPT_BYTES = 72;

    public const COMMON = [
        'password1234', 'password12345', 'password123456', 'passwordpassword',
        '123456789012', '1234567890123', '12345678901234', '1234567890123456',
        'qwertyuiop12', 'qwertyuiop123', 'qwerty123456', 'qwertyqwerty',
        'administrator', 'administrator123', 'adminadmin123', 'letmeinletmein',
        'welcome12345', 'iloveyou1234', 'changeme1234', 'correcthorsebatterystaple',
    ];

    /** @return list<string|Closure> */
    public static function rules(bool $confirmed = true): array
    {
        $rules = ['bail', 'required', 'string', 'max:'.self::MAXIMUM];

        if ($confirmed) {
            array_push($rules, 'min:'.self::MINIMUM, 'confirmed');
        }

        $rules[] = static function (string $attribute, #[\SensitiveParameter] string $value, Closure $fail) use ($confirmed): void {
            if (str_contains($value, "\0") || (self::usesBcrypt() && strlen($value) > self::BCRYPT_BYTES)) {
                $fail('Use a password that fits the supported length and character limits.');
            } elseif ($confirmed && self::predictable($value)) {
                $fail('Choose a less common password without repeated or sequential patterns.');
            }
        };

        return $rules;
    }

    public static function usesBcrypt(): bool
    {
        return config('hashing.driver', 'bcrypt') === 'bcrypt';
    }

    private static function predictable(#[\SensitiveParameter] string $value): bool
    {
        $value = mb_strtolower(trim($value));
        if ($value === '' || in_array($value, self::COMMON, true) || preg_match('/\A(.{1,4})\1+\z/su', $value)) {
            return true;
        }

        foreach (['abcdefghijklmnopqrstuvwxyz', '0123456789', 'qwertyuiopasdfghjklzxcvbnm'] as $sequence) {
            if (str_contains($sequence.$sequence, $value) || str_contains(strrev($sequence.$sequence), $value)) {
                return true;
            }
        }

        return false;
    }
}
