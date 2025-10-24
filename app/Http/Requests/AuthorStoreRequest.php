<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AuthorStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }
    public function rules()
    {
        return [
            'author_name' => 'required|string|max:255',
            'disambiguator' => 'nullable|string',
        ];
    }
}
