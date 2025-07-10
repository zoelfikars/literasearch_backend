<?php

namespace App\Http\Requests\books;

use Illuminate\Foundation\Http\FormRequest;

class BookFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', 'string', 'in:id,title,author,published_at'],
            'order' => ['sometimes', 'string', 'in:asc,desc'],
            'search' => ['sometimes', 'string'],
            'range' => ['sometimes', 'string', 'in:daily,weekly,monthly,yearly'],
        ];
    }
}
