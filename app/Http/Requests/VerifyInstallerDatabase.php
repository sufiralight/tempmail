<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Session\Store;

final class VerifyInstallerDatabase extends FormRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        $access = $this->attributes->get('installer.access');

        return [
            'host' => ['bail', 'required', 'string', 'max:253', function (string $attribute, mixed $value, Closure $fail) use ($access): void {
                $host = strtolower($value);
                if ($access['local'] ? ! in_array($host, ['localhost', '127.0.0.1'], true) : $host !== $access['host']) {
                    $fail('Enter the database host authorized for this installation.');
                }
            }],
            'port' => ['bail', 'required', 'integer', 'between:1,65535', function (string $attribute, mixed $value, Closure $fail) use ($access): void {
                if (! $access['local'] && (int) $value !== $access['port']) {
                    $fail('Enter the database port authorized for this installation.');
                }
            }],
            'database' => ['required', 'string', 'max:64', 'regex:/\A[a-zA-Z0-9_-]+\z/'],
            'username' => ['required', 'string', 'max:80', 'not_regex:/[\x00-\x1F\x7F]/'],
            'database_password' => [$access['local'] ? 'nullable' : 'required', 'string', 'max:512', 'not_regex:/\x00/'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['host' => 'database host', 'port' => 'database port', 'database' => 'database name', 'username' => 'database username'];
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException((new RedirectResponse('/install'))->setSession(app(Store::class))->withErrors($validator));
    }

    /** @return array{host: string, port: int, database: string, username: string, password: string} */
    public function settings(): array
    {
        $values = $this->validated();
        $host = strtolower($values['host']);

        return [
            'host' => $host === 'localhost' ? '127.0.0.1' : $host,
            'port' => (int) $values['port'],
            'database' => $values['database'],
            'username' => $values['username'],
            'password' => (string) ($values['database_password'] ?? ''),
        ];
    }
}
