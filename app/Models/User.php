<?php

namespace App\Models;

use App\Notifications\VerifyApiEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasRoles, HasApiTokens, HasFactory, Notifiable, HasUuids, SoftDeletes;
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
    public function sendCustomEmailVerificationNotification(string $platform)
    {
        $this->notify(new VerifyApiEmail($platform));
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
    public function identity()
    {
        return $this->hasOne(UserIdentity::class);
    }
    public function otp()
    {
        return $this->hasMany(OtpCode::class);
    }
    public function managedLibraries()
    {
        return $this->belongsToMany(Library::class, 'library_librarians')
            ->withPivot(['is_active'])
            ->withTimestamps();
    }
    public function managedLibrariesActive()
    {
        return $this->belongsToMany(Library::class, 'library_librarians')
            ->withPivot(['is_active'])
            ->wherePivot('is_active', true)
            ->withTimestamps();
    }

    public function librarianApplications()
    {
        return $this->hasMany(LibrarianApplication::class);
    }
    public function owners()
    {
        return $this->hasMany(Library::class, 'owner_id');
    }
    public function ratedLibraries()
    {
        return $this->belongsToMany(Library::class, 'library_ratings')
            ->withPivot('rating')
            ->withTimestamps();
    }
    public function comments()
    {
        return $this->hasMany(LibraryComment::class, 'user_id');
    }
    public function memberships()
    {
        return $this->hasMany(LibraryLibrarian::class, 'user_id');
    }
}
