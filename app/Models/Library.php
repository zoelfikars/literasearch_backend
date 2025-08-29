<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;

class Library extends Model
{
    use HasFactory, HasUuids, SoftDeletes;
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'name',
        'description',
        'address',
        'phone_number',
        'latitude',
        'longitude',
        'is_active',
        'is_recruiting',
        'image_path',
        'owner_id',
    ];
    protected function coverUrlCacheKey(): string
    {
        $version = md5(($this->image_path ?? '') . '|' . optional($this->updated_at)->timestamp);
        return "library:{$this->getKey()}:cover_url:{$version}";
    }
    public function getCoverSignedUrlAttribute(): ?string
    {
        if (empty($this->image_path)) {
            return null;
        }
        $ttl = now()->addMinutes(10)->subSeconds(5);
        return Cache::remember($this->coverUrlCacheKey(), $ttl, function () {
            return URL::temporarySignedRoute(
                'api.libraries.cover',
                now()->addMinutes(10),
                ['id' => $this->getKey()]
            );
        });
    }
    public function editions()
    {
        return $this->belongsToMany(Edition::class, 'edition_library_stocks')
            ->withPivot('stock_total', 'stock_available');
    }
    public function applications()
    {
        return $this->hasMany(LibraryApplication::class, 'library_id');
    }
    public function pendingApplications()
    {
        return $this->applications()->pending();
    }
    public function latestApprovedByExpiration()
    {
        return $this->hasOne(LibraryApplication::class, 'library_id')
            ->approved()
            ->orderByDesc('expiration_date');
    }
    public function latestPending()
    {
        return $this->hasOne(LibraryApplication::class, 'library_id')
            ->pending()
            ->orderByDesc('created_at');
    }
    public function librarians()
    {
        return $this->belongsToMany(User::class, 'library_librarians')
            ->withPivot(['is_active'])
            ->withTimestamps();
    }
    public function librarianApplications()
    {
        return $this->hasMany(LibrarianApplication::class);
    }
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
    public function raters()
    {
        return $this->belongsToMany(User::class, 'library_ratings')
            ->withPivot('rating')
            ->withTimestamps();
    }
    public function ratingRecords()
    {
        return $this->hasMany(LibraryRating::class);
    }
    public function comments()
    {
        return $this->hasMany(LibraryComment::class, 'library_id');
    }

    // scope
    public function scopeWithRatingsAgg(Builder $q)
    {
        return $q->withAvg(
            [
                'ratingRecords as ratings_avg_rating' => fn($qq) =>
                    $qq->whereHas('user', fn($u) => $u->whereNull('users.deleted_at'))
            ],
            'rating'
        );
    }
    public function scopeWithCounts(Builder $q)
    {
        return $q->withCount([
            'ratingRecords as ratings_count' => fn($qq) =>
                $qq->whereHas('user', fn($u) => $u->whereNull('users.deleted_at')),
            'pendingApplications'
        ]);
    }

    public function scopeActiveFor(Builder $q, ?User $user)
    {
        if (!$user || !($user->hasRole('Pustakawan Nasional') || $user->hasRole('Super Admin'))) {
            $q->where('is_active', true);
        }
        return $q;
    }
    public function scopeOrderByDistance(Builder $q, float $lat, float $lng)
    {
        return $q->selectRaw("
            libraries.*,
            (
              6371 * acos(
                cos(radians(?)) *
                cos(radians(latitude)) *
                cos(radians(longitude) - radians(?)) +
                sin(radians(?)) *
                sin(radians(latitude))
              )
            ) AS distance
        ", [$lat, $lng, $lat])->orderBy('distance');
    }
    public function scopeOrderByAllowed(Builder $q, string $sort, string $order)
    {
        $allowed = [
            'id' => 'libraries.id',
            'name' => 'libraries.name',
            'rating' => 'ratings_avg_rating',
            'rating_count' => 'ratings_count',
            'created_at' => 'libraries.created_at',
        ];
        $col = $allowed[$sort] ?? 'libraries.id';
        $dir = strtolower($order) === 'desc' ? 'desc' : 'asc';
        return $q->orderBy($col, $dir);
    }

}
