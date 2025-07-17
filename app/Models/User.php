<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Model
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
    // public function roles()
    // {
    //     return $this->belongsToMany(Role::class, 'user_roles');
    // }
}
