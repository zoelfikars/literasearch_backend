<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Edition extends Model
{
    use HasFactory, HasUuids;
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
        'book_id',
        'publisher_id',
        'language_id'
    ];
    protected $keyType = 'string';
    public $incrementing = false;
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(Publisher::class);
    }
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class, 'edition_authors')->withPivot('role', 'subtitle');
    }
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'editions_subjects');
    }
    public function libraries(): BelongsToMany
    {
        return $this->belongsToMany(Library::class, 'edition_library_stocks')
            ->withPivot('stock_total', 'stock_available');
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
}
