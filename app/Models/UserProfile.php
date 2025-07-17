<?php

namespace App\Models;

use Crypt;
use Hash;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasUuids;
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $fillable = [
        'user_id',
        'full_name',
        'nik',
        'nik_hash',
        'birth_place',
        'birth_date',
        'gender',
        'address',
        'phone_number',
        'profile_picture_path',
        'identity_image_path',
        'selfie_image_path',
    ];
    protected $casts = [
        'full_name' => 'encrypted',
        'birth_place' => 'encrypted',
        'birth_date' => 'encrypted',
        'address' => 'encrypted',
    ];
    public function setNikAttribute(?string $value): void
    {
        if (is_null($value)) {
            $this->attributes['nik'] = null;
            $this->attributes['nik_hash'] = null;
        } else {
            $this->attributes['nik_hash'] = Hash::make($value);
            $this->attributes['nik'] = Crypt::encryptString($value);
        }
    }
    public function getNikAttribute(?string $value): ?string
    {
        if (is_null($value)) {
            return null;
        }
        try {
            return Crypt::decryptString($value);
        } catch (DecryptException $e) {
            \Log::error('Could not decrypt NIK for user ' . $this->user_id . ': ' . $e->getMessage());
            return null;
        }
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
