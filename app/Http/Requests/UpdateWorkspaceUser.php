<?php

namespace App\Http\Requests;

use App\Auth\WorkspaceRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateWorkspaceUser extends FormRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'role' => ['required', Rule::enum(WorkspaceRole::class)],
            'active' => ['required', 'boolean'],
            'confirmed' => ['accepted'],
        ];
    }
}
