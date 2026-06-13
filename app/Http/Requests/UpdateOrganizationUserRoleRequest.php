<?php

namespace App\Http\Requests;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationUserRoleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'role' => ['required', 'string', Rule::in([
                Organization::ROLE_ADMIN,
                Organization::ROLE_MEMBER,
            ])],
        ];
    }
}
