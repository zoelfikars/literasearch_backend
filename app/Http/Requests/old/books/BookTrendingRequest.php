<?php

namespace App\Http\Requests\books;

use Illuminate\Foundation\Http\FormRequest;

class BookTrendingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'order' => ['sometimes', 'string', 'in:asc,desc'],
            'search' => ['sometimes', 'string'],
            'range' => ['sometimes', 'string', 'in:daily,weekly,monthly,yearly'],
        ];
    }
}
