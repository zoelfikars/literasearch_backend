<?php

namespace App\Http\Requests\books;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookUserRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'book_id' => ['required', 'exists:books,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
        ];
    }
}
