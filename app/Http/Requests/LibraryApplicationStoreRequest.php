<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LibraryApplicationStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            "name" => "required|string|max:255",
            "description" => "nullable|string",
            "address" => "required|string",
            "phone" => ["required", "string", "regex:/^08[0-9]{9,11}$/"],
            "latitude" => "required|numeric",
            "longitude" => "required|numeric",
            "document" => "required|file|mimes:pdf,doc,docx|max:20480",
            "expiration_date" => "required|date",
            "image" => "required|image|mimes:jpeg,png,jpg|max:20480",
            "recruitment" => "required|boolean",
        ];
    }
}
