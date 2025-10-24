<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            "nickname" => ["sometimes", "nullable", "string", "max:50"],
            "email" => ["sometimes", "nullable", "email", "max:191", "unique:users,email," . $this->user()->id],
            "profile_picture" => ["sometimes", "nullable", "image", "max:2048"],
        ];
    }
}
