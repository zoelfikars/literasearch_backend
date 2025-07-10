<?php

namespace App\Http\Requests\libraries;

use Illuminate\Foundation\Http\FormRequest;

class LibraryFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', 'string', 'in:id,name,distance'],
            'order' => ['sometimes', 'string', 'in:asc,desc'],
            'search' => ['sometimes', 'string'],
            'lon' => ['sometimes', 'numeric', 'between:-180,180'],
            'lat' => ['sometimes', 'numeric', 'between:-90,90'],
        ];
    }
}
