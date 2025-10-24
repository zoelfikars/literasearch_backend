<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LibrarianFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'section' => ['sometimes', 'in:librarians,applications'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'search' => ['sometimes', 'string'],
            'sort' => ['sometimes', 'in:created_at,name,is_active'],
            'order' => ['sometimes', 'in:asc,desc'],
        ];
    }
}
