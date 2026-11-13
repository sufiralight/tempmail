<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UploadMedia extends FormRequest
{
    public function messages(): array
    {
        return [
            'image.uploaded' => 'PHP could not receive this file. Try a smaller image or ask the operator to check the upload limits.',
            'image.max' => 'The file exceeds the :max KiB upload limit.',
            'image.extensions' => 'Choose a JPEG, PNG, WebP or GIF image.',
            'image.mimetypes' => 'The file is unsupported or does not contain a valid image.',
        ];
    }

    public function rules(): array
    {
        return ['image' => ['required', 'file', 'max:'.config('media.max_kib'), 'extensions:jpg,jpeg,png,webp,gif', 'mimetypes:image/jpeg,image/png,image/webp,image/gif']];
    }
}
