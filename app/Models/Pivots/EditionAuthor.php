<?php

namespace App\Models\Pivots;

use App\Models\Author;
use App\Models\AuthorRole;
use App\Models\Edition;
use Illuminate\Database\Eloquent\Relations\Pivot;

class EditionAuthor extends Pivot
{
    protected $fillable = [
        'edition_id',
        'author_id',
        'role_id',
        'subtitle',
    ];
    public function edition()
    {
        return $this->belongsTo(Edition::class);
    }
    public function author()
    {
        return $this->belongsTo(Author::class);
    }
    public function role()
    {
        return $this->belongsTo(AuthorRole::class, 'role_id');
    }
}
