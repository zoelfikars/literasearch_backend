<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SelfieProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return false;
    }
    public function rules(): array
    {
        return [
            'selfie_image' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
        ];
    }
}
