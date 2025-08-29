<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LibraryEditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            "name" => "sometimes|string",
            "address" => "sometimes|string",
            "description" => "sometimes|string",
            "phone" => [
                "required",
                "string",
                "regex:/^(?:08[0-9]{8,11}|\+628[0-9]{8,11}|0[2-7][0-9]{7,10})$/",
            ],
            "latitude" => "sometimes|numeric",
            "longitude" => "sometimes|numeric",
            "recruitment" => "sometimes|boolean",
            "image" => "sometimes|file|mimes:jpg,jpeg,png|max:5096",
        ];
    }
    protected function prepareForValidation()
    {
        if ($this->filled('phone')) {
            $raw = (string) $this->input('phone');
            $clean = preg_replace('/(?!^\+)[^\d]/', '', $raw);
            $this->merge(['phone' => $clean]);
        }
    }
}
