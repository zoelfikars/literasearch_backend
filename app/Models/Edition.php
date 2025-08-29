<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Edition extends Model
{
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
        'edition_title_id',
        'publisher_id',
        'language_id'
    ];
    protected $keyType = 'string';
    public $incrementing = false;
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
    public function authors()
    {
        return $this->belongsToMany(Author::class, 'edition_authors')->withPivot('role', 'subtitle');
    }
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'books_subjects');
    }
    public function libraries()
    {
        return $this->belongsToMany(Library::class, 'edition_library_stocks')
            ->withPivot('stock_total', 'stock_available');
    }
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

    // scope
    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (!$term) return $q;

        $like = '%'.$term.'%';

        return $q->where(function ($w) use ($like) {
                $w->where('isbn_10', 'like', $like)
                  ->orWhere('isbn_13', 'like', $like)
                  ->orWhere('subtitle', 'like', $like)
                  ->orWhere('description', 'like', $like);
            })
            ->orWhereHas('title', function ($t) use ($like) {
                $t->where('name', 'like', $like);
            })
            ->orWhereHas('authors', function ($a) use ($like) {
                $a->where('authors.name', 'like', $like);
            })
            ->orWhereHas('subjects', function ($s) use ($like) {
                $s->where('subjects.name', 'like', $like);
            });
    }

}
