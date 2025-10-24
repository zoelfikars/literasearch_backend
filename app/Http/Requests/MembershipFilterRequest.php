<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MembershipFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'section' => ['sometimes', 'in:members,applications'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'search' => ['sometimes', 'string'],
            'sort' => ['sometimes', 'string', 'in:is_active,inactive,pending,name,created_at'],
            'order' => ['sometimes', 'in:asc,desc'],
        ];
    }
}
