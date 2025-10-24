<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LibraryStoreEditionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'edition_id' => ['required', 'uuid', 'exists:editions,id'],
            'stock' => ['required', 'integer', 'min:1'],
        ];
    }
}
