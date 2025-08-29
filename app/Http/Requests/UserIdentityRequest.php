<?php

namespace App\Http\Requests;

use App\Rules\UniquePhoneNumberRule;
use Illuminate\Foundation\Http\FormRequest;

class UserIdentityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            "has_own_ktp" => "required|boolean",
            "identity_image" => "required|file|mimes:jpg,jpeg,png|max:5096",
            "phone" => [
                "required",
                "string",
                "regex:/^(?:08[0-9]{8,11}|\+628[0-9]{8,11}|0[2-7][0-9]{7,10})$/",
                new UniquePhoneNumberRule($this->user()->id)
            ],
            "relationship" => "required_if:has_own_ktp,false|string|in:Ayah,Ibu,Kakak,Wali",
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
