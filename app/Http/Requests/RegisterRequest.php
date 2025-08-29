<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            "platform" => "required|in:web,mobile",
            "nickname" => "nullable|string|max:50",
            "email" => "required|email|unique:users,email",
            "password" => "required|string|min:8|confirmed|",
        ];
    }
}
