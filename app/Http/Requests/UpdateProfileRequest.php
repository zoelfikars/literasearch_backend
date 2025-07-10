<?php

namespace App\Http\Requests;

use App\Models\UserProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'profile_image' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
            'full_name' => 'nullable|string|max:255',
            'nik' => [
                'nullable',
                'string',
                'regex:/^[0-9]{16}$/',
                function ($attribute, $value, $fail) {
                    if (!is_null($value)) {
                        $existingProfile = UserProfile::where(DB::raw('BINARY nik_hash'), Hash::make($value))->first();
                        if ($existingProfile) {
                            $fail('Nomor Induk Kependudukan (NIK) ini sudah terdaftar.');
                        }
                    }
                }
            ],
            'birth_place' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
            'address' => 'nullable|string|max:500',
            'profile_picture_path' => 'nullable|string|max:255',
            'identity_image_path' => 'nullable|string|max:255',
            'selfie_image_path' => 'nullable|string|max:255',
        ];
    }
}
