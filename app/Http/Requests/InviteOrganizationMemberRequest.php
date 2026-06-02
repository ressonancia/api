<?php

namespace App\Http\Requests;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InviteOrganizationMemberRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'role' => ['required', 'string', Rule::in([
                Organization::ROLE_ADMIN,
                Organization::ROLE_USER,
            ])],
        ];
    }
}
