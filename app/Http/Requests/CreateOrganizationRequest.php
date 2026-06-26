<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrganizationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:200',
        ];
    }
}
