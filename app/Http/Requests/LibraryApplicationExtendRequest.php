<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LibraryApplicationExtendRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            "document" => "required|file|mimes:pdf,doc,docx|max:20480",
            "expiration_date" => [
                "required",
                "date",
                "after:today",
            ],
        ];
    }
}
