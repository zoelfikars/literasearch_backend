<?php

namespace App\Http\Requests;

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
            "search" => "sometimes|string",
            "page" => "sometimes|integer|min:1",
            "per_page" => "sometimes|integer|min:1|max:50",
            "latitude" => "required|numeric",
            "longitude" => "required|numeric",
        ];
    }
}
