<?php

namespace App\Models;


use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\URL;

class UserIdentity extends Model
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
        'phone_number_hash',
        'profile_picture_path',
        'identity_image_path',
        'relationship',
    ];
    protected $casts = [
        'full_name' => 'encrypted',
        'birth_place' => 'encrypted',
        'birth_date' => 'encrypted',
        'address' => 'encrypted',
        'phone_number' => 'encrypted',
        'updated_at' => 'datetime',
    ];
    protected function signedUrlCacheKey(): string
    {
        $ver = md5(($this->identity_image_path ?? '') . '|' . optional($this->updated_at)->timestamp);
        return "identity:signed_url:{$this->getKey()}:{$ver}";
    }

    public function getSignedUrlAttribute(): ?string
    {
        if (empty($this->identity_image_path) || empty($this->user_id)) {
            return null;
        }
        $ttl = now()->addMinutes(10)->subSeconds(5);
        return Cache::remember($this->signedUrlCacheKey(), $ttl, function () {
            return URL::temporarySignedRoute(
                'api.user.identity.picture',
                now()->addMinutes(10),
                [
                    'user' => $this->user_id,
                ]
            );
        });
    }
    public function setNikAttribute(?string $value): void
    {
        if (is_null($value)) {
            $this->attributes['nik'] = null;
            $this->attributes['nik_hash'] = null;
        } else {
            $this->attributes['nik'] = Crypt::encryptString($value);
            $this->attributes['nik_hash'] = hash('sha256', $value);
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
            return null;
        }
    }
    public function setPhoneNumberAttribute(?string $value): void
    {
        if (is_null($value)) {
            $this->attributes['phone_number'] = null;
            $this->attributes['phone_number_hash'] = null;
        } else {
            $this->attributes['phone_number'] = Crypt::encryptString($value);
            $this->attributes['phone_number_hash'] = hash('sha256', $value);
        }
    }
    public function getPhoneNumberAttribute(?string $value): ?string
    {
        if (is_null($value)) {
            return null;
        }
        try {
            return Crypt::decryptString($value);
        } catch (DecryptException $e) {
            return null;
        }
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
