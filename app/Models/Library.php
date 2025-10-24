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
        if ($user && ($user->hasRole('Pustakawan Nasional') || $user->hasRole('Super Admin'))) {
            return $q;
        }
        return $q->where('is_active', true);
    }
    public function scopeWithDistance(Builder $q, float $lat, float $lng)
    {
        return $q->selectRaw("
            (
              6371 * acos(
                cos(radians(?)) *
                cos(radians(latitude)) *
                cos(radians(longitude) - radians(?)) +
                sin(radians(?)) *
                sin(radians(latitude))
              )
            ) AS distance
        ", [$lat, $lng, $lat]);
    }
    public function scopeOrderByDistance(Builder $q, float $lat, float $lng)
    {
        return $q->withDistance($lat, $lng)->orderBy('distance');
    }
    public function scopeSearch(Builder $q, ?string $search): Builder
    {
        $search = trim((string) $search);
        if ($search === '')
            return $q;
        return $q->where(function ($qq) use ($search) {
            $qq->where('libraries.name', 'like', "%{$search}%")
                ->orWhere('libraries.address', 'like', "%{$search}%")
                ->orWhere('libraries.description', 'like', "%{$search}%");
        });
    }
    public function scopeApplySort(Builder $q, string $sort, string $order = 'asc', array $opts = []): Builder
    {
        $order = strtolower($order) === 'desc' ? 'desc' : 'asc';
        $editionId = $opts['edition_id'] ?? null;
        $hasIsLibr = $opts['has_is_librarian_exists'] ?? false;
        switch ($sort) {
            case 'distance':
                $q->orderBy('distance', $order)
                    ->orderBy('libraries.id', 'asc');
                break;
            case 'stock':
                if ($editionId) {
                    $q->orderByRaw('COALESCE(selected_stock_total, 0) ' . $order);
                } else {
                    $q->orderByRaw('COALESCE(physical_stock_total, 0) ' . $order);
                }
                $q->orderBy('libraries.id', 'asc');
                break;
            case 'rating':
                $q->orderByRaw('COALESCE(ratings_avg_rating, 0) ' . $order)
                    ->orderBy('libraries.id', 'asc');
                break;
            case 'inspection':
                $q->orderBy('has_inspection', $order)
                    ->orderBy('libraries.id', 'asc');
                break;
            case 'is_librarian':
                if ($hasIsLibr) {
                    $q->orderBy('is_librarian_exists', $order)
                        ->orderBy('libraries.id', 'asc');
                } else {
                    $q->orderBy('libraries.id', 'asc');
                }
                break;
            default:
                $q->orderByAllowed($sort, $order);
                break;
        }
        return $q;
    }
    public function scopeLimitHaversineCandidates(Builder $q, int $maxCandidates): Builder
    {
        return $q->orderBy('distance', 'asc')->limit($maxCandidates);
    }
    public function scopeApplyOrderByIds(Builder $q, array $sortedIds, string $order = 'asc'): Builder
    {
        if (empty($sortedIds)) {
            return $q->orderBy('distance', $order)->orderBy('libraries.id', 'asc');
        }
        $placeholders = implode(',', array_fill(0, count($sortedIds), '?'));
        return $q->whereIn('libraries.id', $sortedIds)
            ->orderByRaw(
                "CASE WHEN FIELD(libraries.id, $placeholders)=0 THEN 1 ELSE 0 END",
                $sortedIds
            )
            ->orderByRaw("FIELD(libraries.id, $placeholders)", $sortedIds)
            ->orderBy('distance', $order)
            ->orderBy('libraries.id', 'asc');
    }
    public function scopeOrderByAllowed(Builder $q, string $sort, string $order)
    {
        $allowed = [
            'id' => 'libraries.id',
            'name' => 'libraries.name',
            'rating_count' => 'ratings_count',
            'created_at' => 'libraries.created_at',
        ];
        $col = $allowed[$sort] ?? 'libraries.id';
        $dir = strtolower($order) === 'desc' ? 'desc' : 'asc';
        return $q->orderBy($col, $dir);
    }
    public function scopeWhereHasEdition(Builder $q, string $editionId): Builder
    {
        return $q->whereHas('editions', fn($qq) => $qq->where('editions.id', $editionId));
    }
    protected function coverVersion(): ?string
    {
        if (empty($this->image_path))
            return null;
        $disk = \Storage::disk('private');
        if (!$disk->exists($this->image_path))
            return null;
        $last = $disk->lastModified($this->image_path);
        $size = $disk->size($this->image_path);
        return substr(sha1($this->image_path . '|' . $last . '|' . $size), 0, 16);
    }
    public function getCoverUrlAttribute(): ?string
    {
        if (empty($this->image_path))
            return null;
        $params = ['library' => $this->getKey()];
        if ($ver = $this->coverVersion()) {
            $params['v'] = $ver;
        }
        return URL::route('api.libraries.cover', $params);
    }
    public function getCoverSignedUrlAttribute(): ?string
    {
        return $this->cover_url;
    }
    public function editions()
    {
        return $this->belongsToMany(Edition::class, 'library_editions', 'library_id', 'edition_id')
            ->withPivot(['stock_total'])
            ->withTimestamps();
    }
    public function libraryEditions()
    {
        return $this->hasMany(LibraryEdition::class, 'library_id', 'id');
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
    public function firstApproved()
    {
        return $this->hasOne(LibraryApplication::class, 'library_id')
            ->approved()
            ->orderBy('created_at');
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
    public function members()
    {
        return $this->belongsToMany(User::class, 'library_members')
            ->withPivot(['is_active'])
            ->withTimestamps();
    }
    public function membershipApplications()
    {
        return $this->hasMany(MembershipApplication::class);
    }
    public function pendingMembershipApplications()
    {
        return $this->membershipApplications()->pending();
    }
    public function approvedMembershipApplications()
    {
        return $this->membershipApplications()->approved();
    }
    public function rejectedMembershipApplications()
    {
        return $this->membershipApplications()->rejected();
    }
    public function loans()
    {
        return $this->hasMany(Loan::class);
    }
}
