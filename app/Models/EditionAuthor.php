<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class EditionAuthor extends Model
{
    use HasUuids;
    public $incrementing = false;
    protected $fillable = [
        'edition_id',
        'author_id',
        'role',
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
}
