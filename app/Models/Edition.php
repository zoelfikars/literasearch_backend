<?php
namespace App\Models;
use App\Models\Pivots\EditionRating;
use App\Models\Pivots\EditionWishlist;
use Cache;
use DB;
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
        'publication_year',
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
    protected function coverVersion(): ?string
    {
        if (empty($this->cover))
            return null;
        $disk = \Storage::disk('private');
        if (!$disk->exists($this->cover))
            return null;
        $last = $disk->lastModified($this->cover);
        $size = $disk->size($this->cover);
        return substr(sha1($this->cover . '|' . $last . '|' . $size), 0, 16);
    }
    public function getCoverUrlAttribute(): ?string
    {
        if (empty($this->cover))
            return null;
        $params = ['book' => $this->getKey()];
        if ($ver = $this->coverVersion()) {
            $params['v'] = $ver;
        }
        return URL::route('api.books.cover', $params);
    }
    public function getCoverSignedUrlAttribute(): ?string
    {
        return $this->cover_url;
    }
    public function getEbookUrlAttribute(): ?string
    {
        if (empty($this->file_path)) {
            return null;
        }
        return URL::route('api.books.read', ['book' => $this->getKey()]);
    }
    public function getEbookMimeAttribute(): ?string
    {
        return $this->file_path
            ? \Storage::disk('private')->mimeType($this->file_path)
            : null;
    }
    public function getEbookSizeAttribute(): ?int
    {
        return $this->file_path
            ? \Storage::disk('private')->size($this->file_path)
            : null;
    }
    public function getEbookExtAttribute(): ?string
    {
        return $this->file_path
            ? pathinfo($this->file_path, PATHINFO_EXTENSION)
            : null;
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
    public function scopeInLibrary(Builder $q, string $libraryId)
    {
        return $q->whereHas('libraries', function ($l) use ($libraryId) {
            $l->where('libraries.id', $libraryId);
        });
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
            ->join('author_roles as ar', 'ar.id', '=', 'edition_authors.role_id')
            ->addSelect([
                'authors.*',
                'edition_authors.role_id as contributor_role_id',
                'ar.name as contributor_role_name',
            ])
            ->distinct()
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
            ->join('author_roles as ar', 'ar.id', '=', 'edition_authors.role_id')
            ->addSelect([
                'authors.*',
                'edition_authors.role_id as writer_role_id',
                'ar.name as writer_role_name',
            ])
            ->whereIn('edition_authors.role_id', function ($q) {
                $q->select('id')->from('author_roles')->where('slug', self::WRITER_ROLE_SLUG);
            })
            ->distinct()
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
        return $this->belongsToMany(Library::class, 'library_editions', 'edition_id', 'library_id')
            ->withPivot(['stock_total'])
            ->withTimestamps();
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
    public function readPositions()
    {
        return $this->hasMany(EditionReadPosition::class);
    }
    public function readers()
    {
        return $this->belongsToMany(User::class, 'edition_read_positions')
            ->withPivot(['locator_type', 'page', 'cfi', 'progress_percent', 'last_opened_at'])
            ->withTimestamps();
    }
    public function scopeWishlistByUser($query, $userId)
    {
        return $query->whereHas('wishlists', fn($q) => $q->where('user_id', $userId));
    }
    public function scopeOrderByAllowed(Builder $query, string $sort, string $order = 'desc', ?string $library_id): Builder
    {
        $order = strtolower($order) === 'asc' ? 'asc' : 'desc';
        switch ($sort) {
            case 'created_at':
                return $query->orderBy('editions.created_at', $order);
            case 'edition_name':
                $query->leftJoin('book_titles AS bt_sort', 'bt_sort.id', '=', 'editions.book_title_id');
                $query->addSelect('editions.*');
                return $query->orderBy('bt_sort.title', $order);
            case 'book_name':
                return $query->orderBy('editions.subtitle', $order);
            case 'author_name':
                $query->leftJoin('edition_authors AS ew_sort', 'editions.id', '=', 'ew_sort.edition_id')
                    ->leftJoin('authors AS w_sort', 'ew_sort.author_id', '=', 'w_sort.id');
                $query->addSelect('editions.*');
                return $query->orderBy('w_sort.name', $order);
            case 'publisher_name':
                $query->leftJoin('publishers AS p_sort', 'editions.publisher_id', '=', 'p_sort.id');
                $query->addSelect('editions.*');
                return $query->orderBy('p_sort.name', $order);
            case 'rating':
                return $query->orderByRaw("COALESCE(ratings_avg_rating, 0) {$order}");
            case 'rating_count':
                return $query->orderBy('ratings_count', $order);
            case 'publication_year':
                return $query->orderBy('editions.publication_year', $order);
            case 'isbn':
                return $query->orderBy('editions.isbn_13', $order)->orderBy('editions.isbn_10', $order);
            case 'stock_available':
                if (!$library_id) {
                    return $query->orderBy('editions.created_at', $order);
                }
                $query->join('library_editions as el', function ($j) use ($library_id) {
                    $j->on('el.edition_id', '=', 'editions.id')
                        ->where('el.library_id', '=', $library_id);
                });
                $activeLoansSub = \App\Models\Loan::query()
                    ->select(['library_id', 'edition_id', DB::raw('COUNT(*) as active_loans')])
                    ->loanStatus('approved')
                    ->whereNull('returned_at')
                    ->groupBy('library_id', 'edition_id');
                $query->leftJoinSub($activeLoansSub, 'al', function ($j) {
                    $j->on('al.edition_id', '=', 'editions.id')
                        ->on('al.library_id', '=', 'el.library_id');
                });
                $query->addSelect('editions.*');
                $query->addSelect(DB::raw('GREATEST(el.stock_total - COALESCE(al.active_loans, 0), 0) as computed_stock_available'));
                return $query->orderBy('computed_stock_available', $order);
            case 'unfinished_read':
                return $query->orderBy('user_read_progress', $order);
            case 'wishlisted':
                $query->orderByRaw('COALESCE(is_wishlisted, 0) ' . $order);
                return $query;
            default:
                return $query->orderBy('editions.created_at', $order);
        }
    }
    public function comments()
    {
        return $this->hasMany(EditionComment::class, 'edition_id');
    }
}
