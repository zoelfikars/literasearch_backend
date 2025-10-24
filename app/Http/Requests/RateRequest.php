<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            // "rating" => "required|integer|between:1,5",
            "rating" => [
                "required",
                "numeric",
                "regex:/^(?:[1-4](?:\.5)?|5)$/"
            ],
        ];
    }
}
