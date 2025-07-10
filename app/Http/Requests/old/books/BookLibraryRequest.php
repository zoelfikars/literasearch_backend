<?php

namespace App\Http\Requests\books;

use Illuminate\Foundation\Http\FormRequest;

class BookLibraryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'lon' => ['sometimes', 'numeric', 'between:-180,180'],
            'lat' => ['sometimes', 'numeric', 'between:-90,90'],
        ];
    }
}
