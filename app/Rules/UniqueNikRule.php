<?php

namespace App\Rules;

use App\Models\UserIdentity;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueNikRule implements ValidationRule
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
        $hashedNik = hash('sha256', $value);
        $query = UserIdentity::where('nik_hash', $hashedNik);

        if ($this->currentUserId) {
            $query->where('user_id', '!=', $this->currentUserId);
        }

        if ($query->exists()) {
            $fail('NIK ini sudah terdaftar.');
        }
    }
}
