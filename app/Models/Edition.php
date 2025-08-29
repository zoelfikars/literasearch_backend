<?php
namespace App\Models;
use App\Models\Pivots\EditionRating;
use App\Models\Pivots\EditionWishlist;
use Cache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use URL;
class Edition extends Model
{
    public const WRITER_ROLE_SLUG = 'penulis';
    use HasFactory, HasUuids, SoftDeletes;
    protected $fillable = [
        'isbn_10',
        'isbn_13',
        'edition_number',
        'publication_date',
        'cover',
        'file_path',
        'pages',
        'subtitle',
        'description',
        'book_title_id',
        'publisher_id',
        'language_id'
    ];
    protected $keyType = 'string';
    public $incrementing = false;
    protected function coverUrlCacheKey(): string
    {
        $version = md5(($this->cover ?? '') . '|' . optional($this->updated_at)->timestamp);
        return "edition:{$this->getKey()}:cover_url:{$version}";
    }
    public function getCoverSignedUrlAttribute(): ?string
    {
        if (empty($this->cover)) {
            return null;
        }
        $ttl = now()->addMinutes(10)->subSeconds(5);
        return Cache::remember($this->coverUrlCacheKey(), $ttl, function () {
            return URL::temporarySignedRoute(
                'api.books.cover',
                now()->addMinutes(10),
                ['id' => $this->getKey()]
            );
        });
    }
    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (!$term)
            return $q;
        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $term) . '%';
        return $q->where(function ($w) use ($like) {
            $w->where('isbn_10', 'like', $like)
                ->orWhere('isbn_13', 'like', $like)
                ->orWhere('subtitle', 'like', $like)
                ->orWhere('description', 'like', $like)
                ->orWhereHas('title', fn($t) => $t->where('title', 'like', $like))
                ->orWhereHas('publisher', fn($p) => $p->where('name', 'like', $like))
                ->orWhereHas('language', function ($l) use ($like) {
                    $l->where('english_name', 'like', $like)
                        ->orWhere('native_name', 'like', $like)
                        ->orWhere('iso_639_1', 'like', $like)
                        ->orWhere('iso_639_3', 'like', $like);
                })
                ->orWhereHas('contributors', function ($a) use ($like) {
                    $a->where('authors.name', 'like', $like)
                        ->whereIn('edition_authors.role_id', function ($sub) {
                            $sub->select('id')
                                ->from('author_roles')
                                ->where('slug', 'penulis');
                        });
                })
                ->orWhereHas('subjects', fn($s) => $s->where('subjects.name', 'like', $like));
        });
    }
    public function scopeFilter(Builder $q, array $f)
    {
        return $q
            ->when($f['title_id'] ?? null, fn($qq, $v) => $qq->where('book_title_id', $v))
            ->when($f['publisher_id'] ?? null, fn($qq, $v) => $qq->where('publisher_id', $v))
            ->when($f['language_id'] ?? null, fn($qq, $v) => $qq->where('language_id', $v))
            ->when($f['author_id'] ?? null, fn($qq, $v) => $qq->whereHas('contributors', fn($a) => $a->whereKey($v)))
            ->when($f['role_id'] ?? null, fn($qq, $v) => $qq->whereHas('contributors', fn($a) => $a->wherePivot('role_id', $v)))
            ->when(!empty($f['subject_ids'] ?? null), fn($qq) => $qq->whereHas('subjects', fn($s) => $s->whereIn('subjects.id', (array) $f['subject_ids'])));
    }
    public function scopeOnlyRoleSlug(Builder $q, string $slug)
    {
        return $q->whereHas('roles', fn($r) => $r->where('slug', $slug));
    }
    public function scopeWithRatingsAgg($q)
    {
        return $q->withAvg(
            [
                'ratingRecords as ratings_avg_rating' => fn($qq) =>
                    $qq->whereHas('user', fn($u) => $u->whereNull('users.deleted_at'))
            ],
            'rating'
        )->withCount(
                [
                    'ratingRecords as ratings_count' => fn($qq) =>
                        $qq->whereHas('user', fn($u) => $u->whereNull('users.deleted_at'))
                ]
            );
    }
    public function title()
    {
        return $this->belongsTo(BookTitle::class, 'book_title_id', 'id');
    }
    public function publisher()
    {
        return $this->belongsTo(Publisher::class);
    }
    public function language()
    {
        return $this->belongsTo(Language::class);
    }
    public function contributors()
    {
        return $this->belongsToMany(Author::class, 'edition_authors', 'edition_id', 'author_id')
            ->withPivot(['role_id'])
            ->withTimestamps();
    }
    public function roles()
    {
        return $this->belongsToMany(AuthorRole::class, 'edition_authors', 'edition_id', 'role_id')
            ->withPivot(['author_id'])
            ->withTimestamps();
    }
    public function writers()
    {
        return $this->belongsToMany(Author::class, 'edition_authors', 'edition_id', 'author_id')
            ->whereIn('edition_authors.role_id', function ($q) {
                $q->select('id')->from('author_roles')->where('slug', self::WRITER_ROLE_SLUG);
            })
            ->withPivot(['role_id'])
            ->withTimestamps();
    }
    public function subjects()
    {
        return $this->belongsToMany(
            Subject::class,
            'edition_subjects',
            'edition_id',
            'subject_id'
        )->withTimestamps();
    }
    public function libraries()
    {
        return $this->belongsToMany(Library::class, 'edition_library_stocks')
            ->withPivot('stock_total', 'stock_available');
    }
    public function ratingRecords()
    {
        return $this->hasMany(EditionRating::class, 'edition_id', 'id');
    }
    public function raters()
    {
        return $this->belongsToMany(User::class, 'edition_ratings', 'edition_id', 'user_id')
            ->withPivot(['rating'])
            ->withTimestamps();
    }
    public function wishlists()
    {
        return $this->hasMany(EditionWishlist::class);
    }
    public function loans()
    {
        return $this->hasMany(Loan::class);
    }
}
