<?php

namespace App\Rules;

use App\Models\UserIdentity;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniquePhoneNumberRule implements ValidationRule
{
    protected $currentUserId;
    public function __construct($currentUserId = null)
    {
        $this->currentUserId = $currentUserId;
    }
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_null($value) || $value === '') {
            return;
        }
        $hashedPhoneNumber = hash('sha256', $value);
        $query = UserIdentity::where('phone_number_hash', $hashedPhoneNumber);

        if ($this->currentUserId) {
            $query->where('user_id', '!=', $this->currentUserId);
        }

        if ($query->exists()) {
            $fail('Nomor Telepon ini sudah terdaftar.');
        }
    }
}
