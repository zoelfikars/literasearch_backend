<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasRoles, HasApiTokens, HasFactory, Notifiable, HasUuids;
    protected $fillable = [
        'nickname',
        'email',
        'password',
        'status_id',
        'email_verified_at',
        'is_deleted'
    ];
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_deleted' => 'boolean',
    ];
    protected $hidden = [
        'password',
        'remember_token',
    ];
    protected $keyType = 'string';
    public $incrementing = false;
    public function ratings()
    {
        return $this->hasMany(EditionUserRating::class);
    }
    public function wishlists()
    {
        return $this->hasMany(EditionWishlist::class);
    }
    public function loans()
    {
        return $this->hasMany(Loan::class);
    }
    public function status()
    {
        return $this->belongsTo(Status::class);
    }
    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }
    public function guardian()
    {
        return $this->hasOne(UserGuardian::class);
    }
    public function otp()
    {
        return $this->hasMany(OtpCode::class);
    }
    public function passwordResetToken()
    {
        return $this->hasMany(PasswordResetToken::class);
    }
    public function profileMissingFields(): array
    {
        if (!$this->profile) {
            return [
                'full_name',
                'nik',
                'birth_place',
                'birth_date',
                'gender',
                'address',
                'phone_number',
                'identity_image_path'
            ];
        }

        $missing = [];
        $profile = $this->profile;

        if (!$profile->full_name) $missing[] = 'full_name';
        if (!$profile->nik) $missing[] = 'nik';
        if (!$profile->birth_place) $missing[] = 'birth_place';
        if (!$profile->birth_date) $missing[] = 'birth_date';
        if (!$profile->gender) $missing[] = 'gender';
        if (!$profile->address) $missing[] = 'address';
        if (!$profile->phone_number) $missing[] = 'phone_number';
        if (!$profile->identity_image_path) $missing[] = 'identity_image_path';

        return $missing;
    }

    public function isProfileComplete(): bool
    {
        return count($this->profileMissingFields()) === 0;
    }
    public function guardianMissingFields(): array
    {
        if (!$this->guardian) {
            return [
                'full_name',
                'nik',
                'birth_place',
                'birth_date',
                'gender',
                'address',
                'phone_number',
                'identity_image_path'
            ];
        }

        $missing = [];
        $guardian = $this->guardian;

        if (!$guardian->full_name) $missing[] = 'full_name';
        if (!$guardian->nik) $missing[] = 'nik';
        if (!$guardian->birth_place) $missing[] = 'birth_place';
        if (!$guardian->birth_date) $missing[] = 'birth_date';
        if (!$guardian->gender) $missing[] = 'gender';
        if (!$guardian->address) $missing[] = 'address';
        if (!$guardian->phone_number) $missing[] = 'phone_number';
        if (!$guardian->identity_image_path) $missing[] = 'identity_image_path';

        return $missing;
    }

    public function isGuardianComplete(): bool
    {
        return count($this->guardianMissingFields()) === 0;
    }
}
