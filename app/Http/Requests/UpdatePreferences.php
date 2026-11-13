<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdatePreferences extends FormRequest
{
    public function rules(): array
    {
        return [
            'appearance' => ['required', 'in:light,dark,system'],
            'interface_locale' => ['required', 'in:en'],
            'timezone' => ['required', 'string', 'max:64', 'timezone'],
        ];
    }
}
