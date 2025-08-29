<?php

namespace App\Http\Requests;

use App\Rules\UniqueNikRule;
use App\Rules\UniquePhoneNumberRule;
use Illuminate\Foundation\Http\FormRequest;

class UserIdentityUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            "has_own_ktp" => "required|boolean",
            "full_name" => "required|string|max:100",
            "nik" => ["required", "string", "digits:16", new UniqueNikRule($this->user()->id)],
            "birth_place" => "required|string|max:100",
            "birth_date" => "required|date",
            "address" => "required|string|max:255",
            "gender" => "required|in:Laki-Laki,Perempuan",
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
