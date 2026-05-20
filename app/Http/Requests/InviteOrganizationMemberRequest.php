<?php

namespace App\Http\Requests;

use App\Rules\EmailRealValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InviteOrganizationMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
                'max:200',
                new EmailRealValidation,
            ],
            'role' => [
                'required',
                Rule::in(['admin', 'member']),
            ],
        ];
    }
}
