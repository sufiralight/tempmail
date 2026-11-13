<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UploadMedia extends FormRequest
{
    public function rules(): array
    {
        return ['image' => ['required', 'file', 'max:'.config('media.max_kib'), 'extensions:jpg,jpeg,png,webp,gif', 'mimetypes:image/jpeg,image/png,image/webp,image/gif']];
    }
}
