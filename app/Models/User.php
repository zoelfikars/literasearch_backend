<?php

namespace App\Models;

use App\Models\Pivots\EditionRating;
use App\Models\Pivots\EditionWishlist;
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
        return $this->hasMany(Loan::class, 'user_id', 'id');
    }
    public function scopeOverdueLoans($query)
    {
        return $query->whereHas('loans', function ($q) {
            $q->where('due_date', '<', now())
                ->whereNull('returned_at');
        });
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
    public function managedLibrariesActive($libraryId = null)
    {
        $query = $this->belongsToMany(Library::class, 'library_librarians')
            ->withPivot(['is_active'])
            ->wherePivot('is_active', true)
            ->withTimestamps();

        if ($libraryId) {
            $query->where('libraries.id', $libraryId);
        }

        return $query;
    }
    public function librarianApplications()
    {
        return $this->hasMany(LibrarianApplication::class);
    }
    public function librarian()
    {
        return $this->belongsToMany(Library::class, 'library_librarians')
            ->withPivot(['is_active'])
            ->withTimestamps();
    }
    public function owners()
    {
        return $this->hasMany(Library::class, 'owner_id');
    }
    public function libraryRatings()
    {
        return $this->hasMany(LibraryRating::class, 'user_id', 'id');
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
    public function membershipApplications()
    {
        return $this->hasMany(MembershipApplication::class);
    }
    public function membership()
    {
        return $this->belongsToMany(Library::class, 'library_members')
            ->withPivot([
                'is_active'
                // , 'is_blacklist'
            ])
            ->withTimestamps();
    }
    public function membershipActive()
    {
        return $this->belongsToMany(Library::class, 'library_members')
            ->withPivot([
                'is_active'
                // , 'is_blacklist'
            ])
            ->wherePivot('is_active', true)
            ->withTimestamps();
    }
    public function editionRatings()
    {
        return $this->hasMany(EditionRating::class, 'user_id', 'id');
    }
    public function ratedEditions()
    {
        return $this->belongsToMany(Edition::class, 'edition_ratings', 'user_id', 'edition_id')
            ->withPivot(['rating'])
            ->withTimestamps();
    }
    public function membershipInspector()
    {
        return $this->hasMany(MembershipApplication::class, 'inspector_id');
    }
    public function librarianInspector()
    {
        return $this->hasMany(LibrarianApplication::class, 'inspector_id');
    }
    public function wishlist()
    {
        return $this->belongsToMany(Edition::class, 'edition_wishlists', 'user_id', 'edition_id')
            ->withTimestamps();
    }

    public function readPositions()
    {
        return $this->hasMany(EditionReadPosition::class);
    }
    public function readingEditions()
    {
        return $this->belongsToMany(Edition::class, 'edition_read_positions')
            ->withPivot(['locator_type', 'page', 'cfi', 'progress_percent', 'last_opened_at'])
            ->withTimestamps();
    }

}
