<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Model
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids;
    protected $fillable = [
        'username',
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
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }
    public function ratings(): HasMany
    {
        return $this->hasMany(EditionUserRating::class);
    }
    public function wishlists(): HasMany
    {
        return $this->hasMany(EditionWishlist::class);
    }
    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }
    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }
}
